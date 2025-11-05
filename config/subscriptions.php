<?php

return [
    'currency' => 'eur',

    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'prices' => [
                'monthly' => env('STARTER_MONTHLY_PRICE_ID'),
                'yearly' => env('STARTER_YEARLY_PRICE_ID'),
            ],
            'limits' => [
                'organizations' => 1,
                'members' => 2,
                'designs' => 10,
                'certificates_per_month' => 250,
                'custom_branding' => false,
                'priority_support' => false,
            ],
        ],
        'growth' => [
            'name' => 'Growth',
            'prices' => [
                'monthly' => env('GROWTH_MONTHLY_PRICE_ID'),
                'yearly' => env('GROWTH_YEARLY_PRICE_ID'),
            ],
            'limits' => [
                'organizations' => 1,
                'members' => 10,
                'designs' => -1, // unlimited
                'certificates_per_month' => 5000,
                'custom_branding' => true,
                'priority_support' => true,
            ],
        ],
    ],
];
