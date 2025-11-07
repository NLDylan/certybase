<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Design;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $organizationFactory = Organization::factory();

        return [
            'organization_id' => $organizationFactory,
            'design_id' => Design::factory()->for($organizationFactory, 'organization'),
            'creator_id' => User::factory(),
            'name' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'variable_mapping' => [
                'recipient_name' => 'name',
                'recipient_email' => 'email',
                'variables' => [
                    'course' => 'course',
                    'issued_on' => 'issued_on',
                ],
            ],
            'status' => CampaignStatus::Draft,
            'start_date' => null,
            'end_date' => null,
            'certificate_limit' => $this->faker->optional()->numberBetween(5, 50),
            'certificates_issued' => 0,
            'completed_at' => null,
            'completion_reason' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => CampaignStatus::Active,
            'start_date' => now()->toDateString(),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn () => [
            'status' => CampaignStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
