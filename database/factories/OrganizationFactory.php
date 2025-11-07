<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone_number' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'status' => OrganizationStatus::Active,
            'settings' => [
                'branding' => [
                    'primary_color' => $this->faker->hexColor(),
                ],
            ],
        ];
    }
}
