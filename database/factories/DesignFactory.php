<?php

namespace Database\Factories;

use App\Enums\DesignStatus;
use App\Models\Design;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Design>
 */
class DesignFactory extends Factory
{
    protected $model = Design::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'creator_id' => User::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(),
            'design_data' => [
                'version' => '5.3.0',
                'width' => 1684,
                'height' => 1191,
                'background' => '#ffffff',
                'objects' => [
                    [
                        'type' => 'textbox',
                        'left' => 200,
                        'top' => 320,
                        'width' => 800,
                        'height' => 120,
                        'scaleX' => 1,
                        'scaleY' => 1,
                        'angle' => 0,
                        'opacity' => 1,
                        'text' => 'Certificate for {{recipient_name}}',
                        'template' => 'Certificate for {{recipient_name}}',
                        'fontFamily' => 'Arial',
                        'fontSize' => 48,
                        'fontWeight' => '700',
                        'fill' => '#111827',
                        'textAlign' => 'center',
                        'lineHeight' => 1.2,
                        'charSpacing' => 0,
                    ],
                ],
            ],
            'variables' => [
                'recipient_name',
                'course',
            ],
            'settings' => [
                'orientation' => 'landscape',
                'default_font_family' => 'Arial, sans-serif',
            ],
            'status' => DesignStatus::Draft,
        ];
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => DesignStatus::Active,
        ]);
    }
}
