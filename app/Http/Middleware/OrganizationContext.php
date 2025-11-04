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
            $organizationId = $request->route('organization_id') ?? $request->route('organization');

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

        // Set Spatie permissions team ID (this scopes permissions to the organization)
        if ($organizationId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);
            $user->setPermissionsTeamId($organizationId);

            // Get organization with relationships for Inertia
            $organization = $user->organizations()
                ->where('organizations.id', $organizationId)
                ->first();

            // Share organization data with Inertia
            if ($organization) {
                Inertia::share([
                    'organization' => fn () => [
                        'id' => $organization->id,
                        'name' => $organization->name,
                        'status' => $organization->status->value,
                        'has_active_subscription' => $organization->hasActiveSubscription(),
                    ],
                    'organizations' => fn () => $user->organizations()
                        ->wherePivot('status', \App\Enums\OrganizationUserStatus::Active)
                        ->get()
                        ->map(fn ($org) => [
                            'id' => $org->id,
                            'name' => $org->name,
                            'status' => $org->status->value,
                        ])
                        ->toArray(),
                ]);
            }
        } else {
            Inertia::share([
                'organization' => fn () => null,
                'organizations' => fn () => [],
            ]);
        }

        return $next($request);
    }
}
