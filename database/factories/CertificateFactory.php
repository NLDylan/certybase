<?php

namespace Database\Factories;

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\Design;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        $organizationFactory = Organization::factory();
        $designFactory = Design::factory()->for($organizationFactory, 'organization');

        return [
            'organization_id' => $organizationFactory,
            'design_id' => $designFactory,
            'campaign_id' => null,
            'issued_to_user_id' => null,
            'recipient_name' => $this->faker->name(),
            'recipient_email' => $this->faker->unique()->safeEmail(),
            'recipient_data' => [
                'course' => $this->faker->sentence(3),
                'issued_on' => now()->toDateString(),
            ],
            'certificate_data' => null,
            'verification_token' => $this->faker->regexify('[A-Za-z0-9]{64}'),
            'status' => CertificateStatus::Pending,
            'issued_at' => null,
            'expires_at' => null,
            'revoked_at' => null,
            'revocation_reason' => null,
        ];
    }

    public function issued(): self
    {
        return $this->state(fn () => [
            'status' => CertificateStatus::Issued,
            'issued_at' => now(),
        ]);
    }

    public function revoked(?string $reason = null): self
    {
        return $this->state(fn () => [
            'status' => CertificateStatus::Revoked,
            'revoked_at' => now(),
            'revocation_reason' => $reason ?? 'Revoked for testing purposes.',
        ]);
    }
}
