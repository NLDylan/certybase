<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\URL;
use Stripe\StripeClient;

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

    /**
     * Get Stripe price information for display.
     */
    public function getPriceInfo(?string $priceId): ?array
    {
        if (! $priceId) {
            return null;
        }

        try {
            $stripe = new StripeClient(config('cashier.secret'));
            $price = $stripe->prices->retrieve($priceId, ['expand' => ['product']]);

            $amount = $price->unit_amount / 100; // Convert from cents

            return [
                'id' => $price->id,
                'amount' => $amount,
                'currency' => strtoupper($price->currency),
                'interval' => $price->recurring->interval ?? null,
                'interval_count' => $price->recurring->interval_count ?? 1,
                'formatted' => $this->formatPrice($amount, $price->currency),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format price for display.
     */
    private function formatPrice(float $amount, string $currency): string
    {
        return (new \NumberFormatter('en_US', \NumberFormatter::CURRENCY))
            ->formatCurrency($amount, strtoupper($currency));
    }
}
