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

        // Check session for current organization
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
                'icon_url' => $org->icon_url,
                'logo_url' => $org->logo_url,
                'has_growth_plan' => $org->hasGrowthPlan(),
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
                    'has_active_subscription' => $organization->hasActiveSubscription(),
                    'has_growth_plan' => $organization->hasGrowthPlan(),
                    'icon_url' => $organization->icon_url,
                    'logo_url' => $organization->logo_url,
                ] : null,
                'organizations' => fn () => $activeOrganizations,
            ]);
        } else {
            Inertia::share([
                'organization' => fn () => null,
                'organizations' => fn () => $activeOrganizations,
            ]);

            // If no current organization, redirect to the picker unless already there or on auth routes
            $path = '/'.$request->path();
            $isOrgPage = str_starts_with($path, '/organizations') || str_starts_with($path, '/organization');
            $isAuth = str_starts_with($path, '/login') || str_starts_with($path, '/register') || str_starts_with($path, '/logout');
            if (! $isOrgPage && ! $isAuth) {
                return redirect()->route('organizations.index');
            }
        }

        return $next($request);
    }
}
