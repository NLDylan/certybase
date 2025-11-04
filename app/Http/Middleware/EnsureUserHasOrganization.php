<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Check if there's a current organization in session
        $organizationId = Session::get('organization_id');

        if (! $organizationId) {
            // Auto-select first organization if none set
            $firstOrganization = $user->organizations()
                ->wherePivot('status', \App\Enums\OrganizationUserStatus::Active)
                ->first();

            if ($firstOrganization) {
                Session::put('organization_id', $firstOrganization->id);
            } else {
                // No organizations at all
                if ($user->wants_organization) {
                    return redirect()->route('organizations.create');
                }

                return redirect()->route('organizations.index');
            }
        }

        return $next($request);
    }
}
