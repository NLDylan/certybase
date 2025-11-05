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

        return Inertia::render('organizations/Subscription/Index', [
            'organization' => $organization,
            'subscription' => [
                'currency' => config('subscriptions.currency'),
                'plans' => config('subscriptions.plans'),
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

        return redirect()->away($url);
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
