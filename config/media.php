<?php

return [
    // Whitelist of model types that can hold media and their allowed collections
    // Keys are the string values expected from the client in `model_type`
    'allowed_model_types' => [
        'design' => [
            'class' => App\Models\Design::class,
            'collections' => [
                'canvas_images',
                'preview_image',
            ],
        ],
        'certificate' => [
            'class' => App\Models\Certificate::class,
            'collections' => [
                'attachments',
                'images',
            ],
        ],
        'campaign' => [
            'class' => App\Models\Campaign::class,
            'collections' => [
                'assets',
            ],
        ],
        'organization' => [
            'class' => App\Models\Organization::class,
            'collections' => [
                'icon',
                'logo',
            ],
        ],
        'design-template' => [
            'class' => App\Models\DesignTemplate::class,
            'collections' => [
                'assets',
            ],
        ],
    ],
];


