<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class OrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $organizationId = null;

        // Priority 1: Check URL route parameter (like Nightwatch: /environments/{organization_id}/...)
        if ($request->route('organization_id') || $request->route('organization')) {
            $orgParam = $request->route('organization_id') ?? $request->route('organization');
            // Coerce to organization ID if route model bound
            if ($orgParam instanceof \App\Models\Organization) {
                $organizationId = $orgParam->getKey();
            } elseif (is_array($orgParam)) {
                $organizationId = $orgParam['id'] ?? null;
            } else {
                $organizationId = (string) $orgParam;
            }

            // Validate user has access to this organization
            if ($organizationId && $user->hasAccessToOrganization($organizationId)) {
                // Update session to persist the choice
                Session::put('organization_id', $organizationId);
            } else {
                // User doesn't have access, redirect to first accessible org or create one
                $firstOrganization = $user->organizations()
                    ->wherePivot('status', \App\Enums\OrganizationUserStatus::Active)
                    ->first();

                if ($firstOrganization) {
                    // Redirect to same route with valid organization_id
                    $route = $request->route();
                    $parameters = $route->parameters();
                    $parameters['organization_id'] = $firstOrganization->id;

                    return redirect()->route($route->getName(), $parameters);
                }

                return redirect()->route('organizations.create');
            }
        }

        // Priority 2: Check session
        if (! $organizationId) {
            $organizationId = Session::get('organization_id');
        }

        // Priority 3: Auto-select first organization if none set
        if (! $organizationId) {
            $firstOrganization = $user->organizations()
                ->wherePivot('status', \App\Enums\OrganizationUserStatus::Active)
                ->first();

            if ($firstOrganization) {
                $organizationId = $firstOrganization->id;
                Session::put('organization_id', $organizationId);
            }
        }

        // Prepare active organizations list
        $activeOrganizations = $user->organizations()
            ->wherePivot('status', \App\Enums\OrganizationUserStatus::Active)
            ->get()
            ->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'status' => $org->status->value,
            ])
            ->toArray();

        // Set Spatie permissions team ID (this scopes permissions to the organization)
        if ($organizationId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);

            // Get current organization
            $organization = $user->organizations()
                ->where('organizations.id', $organizationId)
                ->first();

            Inertia::share([
                'organization' => fn () => $organization ? [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'status' => $organization->status->value,
                    // Temporarily omit subscription lookup until billing is wired
                    'has_active_subscription' => false,
                ] : null,
                'organizations' => fn () => $activeOrganizations,
            ]);
        } else {
            Inertia::share([
                'organization' => fn () => null,
                'organizations' => fn () => $activeOrganizations,
            ]);
        }

        return $next($request);
    }
}
