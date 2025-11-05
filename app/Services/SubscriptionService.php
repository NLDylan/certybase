<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\URL;

class SubscriptionService
{
    /**
     * Create a Stripe Checkout session URL for the given Organization and price ID.
     */
    public function createCheckoutSession(string $organizationId, string $priceId): string
    {
        $organization = Organization::query()->findOrFail($organizationId);

        // Ensure a Stripe customer exists for the organization
        if (! $organization->hasStripeId()) {
            $organization->createAsStripeCustomer();
        }

        $checkout = $organization
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => URL::route('organization.subscription.index').'?checkout=success',
                'cancel_url' => URL::route('organization.subscription.index').'?checkout=cancelled',
            ]);

        return $checkout->url;
    }

    /**
     * Get the Stripe Billing Portal URL for the given Organization.
     */
    public function billingPortalUrl(string $organizationId): string
    {
        $organization = Organization::query()->findOrFail($organizationId);

        if (! $organization->hasStripeId()) {
            $organization->createAsStripeCustomer();
        }

        return $organization->billingPortalUrl(URL::route('organization.subscription.index'));
    }
}


