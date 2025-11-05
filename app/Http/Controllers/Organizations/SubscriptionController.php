<?php

namespace App\Http\Controllers\Organizations;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /**
     * Display the organization subscription page.
     */
    public function index(): Response
    {
        $organizationId = session('organization_id');

        if (! $organizationId) {
            abort(404, 'No organization selected');
        }

        $organization = \App\Models\Organization::findOrFail($organizationId);

        $this->authorize('view', $organization);

        $plansWithPrices = config('subscriptions.plans');

        // Determine current subscription's price and matching plan/interval
        $current = $organization->subscription('default');
        $currentPriceId = $current?->stripe_price;

        $currentPlanKey = null;
        $currentInterval = null;
        foreach ($plansWithPrices as $key => $plan) {
            if (($plan['prices']['monthly'] ?? null) === $currentPriceId) {
                $currentPlanKey = $key;
                $currentInterval = 'monthly';
                break;
            }
            if (($plan['prices']['yearly'] ?? null) === $currentPriceId) {
                $currentPlanKey = $key;
                $currentInterval = 'yearly';
                break;
            }
        }

        return Inertia::render('organizations/Subscription/Index', [
            'organization' => array_merge($organization->toArray(), [
                'has_active_subscription' => $organization->hasActiveSubscription(),
            ]),
            'subscription' => [
                'currency' => config('subscriptions.currency'),
                'plans' => $plansWithPrices,
                'current' => [
                    'stripe_price' => $currentPriceId,
                    'plan_key' => $currentPlanKey,
                    'interval' => $currentInterval,
                ],
            ],
            'can' => [
                'update' => auth()->user()?->can('update', $organization) === true,
            ],
        ]);
    }

    /**
     * Redirect to Stripe Checkout for a subscription.
     */
    public function checkout(string $priceId, SubscriptionService $subscriptions)
    {
        $organizationId = (string) session('organization_id');

        abort_if(empty($organizationId), 404, 'No organization selected');

        $organization = \App\Models\Organization::findOrFail($organizationId);
        $this->authorize('update', $organization);

        $url = $subscriptions->createCheckoutSession($organizationId, $priceId);

        // Use Inertia external redirect so XHR POST transitions to a full browser visit
        return \Inertia\Inertia::location($url);
    }

    /**
     * Redirect to the Stripe Billing Portal.
     */
    public function portal(SubscriptionService $subscriptions)
    {
        $organizationId = (string) session('organization_id');

        abort_if(empty($organizationId), 404, 'No organization selected');

        $organization = \App\Models\Organization::findOrFail($organizationId);
        $this->authorize('view', $organization);

        $url = $subscriptions->billingPortalUrl($organizationId);

        return redirect()->away($url);
    }
}
