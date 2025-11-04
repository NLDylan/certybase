<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access to billing/subscription routes
        if ($request->routeIs('billing.*', 'subscriptions.*', 'checkout.*')) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $organizationId = Session::get('organization_id');

        if (! $organizationId) {
            return redirect()->route('organizations.create');
        }

        $organization = $user->organizations()
            ->where('organizations.id', $organizationId)
            ->first();

        if (! $organization) {
            return redirect()->route('organizations.create');
        }

        // Check if organization has active subscription or is on trial
        if (! $organization->hasActiveSubscription()) {
            return redirect()->route('billing.index')
                ->with('error', 'You need an active subscription to access this feature.');
        }

        return $next($request);
    }
}
