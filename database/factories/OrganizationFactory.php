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
            'tax_id' => strtoupper($this->faker->bothify('TAX-#######')),
            'coc_number' => strtoupper($this->faker->bothify('COC-####')),
            'address_line1' => $this->faker->streetAddress(),
            'address_line2' => $this->faker->optional()->secondaryAddress(),
            'address_city' => $this->faker->city(),
            'address_state' => $this->faker->state(),
            'address_postal_code' => strtoupper($this->faker->postcode()),
            'address_country' => strtoupper($this->faker->countryCode()),
            'status' => OrganizationStatus::Active,
            'settings' => [
                'branding' => [
                    'primary_color' => $this->faker->hexColor(),
                ],
            ],
        ];
    }
}
