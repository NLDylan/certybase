<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $hasOrganization = $user->organizations()
            ->wherePivot('status', \App\Enums\OrganizationUserStatus::Active)
            ->exists();

        if (! $hasOrganization) {
            if ($user->wants_organization) {
                return redirect()->route('organizations.create');
            }

            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
