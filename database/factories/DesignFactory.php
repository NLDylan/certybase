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
                'canvas' => [
                    'background' => '#ffffff',
                ],
            ],
            'variables' => [
                'recipient_name' => 'recipient_name',
                'course' => 'course',
            ],
            'settings' => [
                'orientation' => 'landscape',
                'size' => 'A4',
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
