<?php

return [
    'currency' => 'eur',

    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'display' => [
                'monthly' => 12.00,
                'yearly' => 120.00, // 2 months off
            ],
            'prices' => [
                'monthly' => env('STARTER_MONTHLY_PRICE_ID'),
                'yearly' => env('STARTER_YEARLY_PRICE_ID'),
            ],
            'limits' => [
                'members' => 2,
                'designs' => 10,
                'certificates_per_month' => 250,
                'custom_branding' => false,
                'priority_support' => false,
            ],
        ],
        'growth' => [
            'name' => 'Growth',
            'display' => [
                'monthly' => 29.00,
                'yearly' => 290.00, // 2 months off
            ],
            'prices' => [
                'monthly' => env('GROWTH_MONTHLY_PRICE_ID'),
                'yearly' => env('GROWTH_YEARLY_PRICE_ID'),
            ],
            'limits' => [
                'members' => 10,
                'designs' => -1, // unlimited
                'certificates_per_month' => 5000,
                'custom_branding' => true,
                'priority_support' => true,
            ],
        ],
    ],
];
