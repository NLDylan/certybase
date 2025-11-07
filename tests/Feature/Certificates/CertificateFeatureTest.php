<?php

declare(strict_types=1);

use App\Enums\CertificateStatus;
use App\Enums\OrganizationUserStatus;
use App\Models\Campaign;
use App\Models\Certificate;
use App\Models\Design;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->seed(Database\Seeders\PermissionsSeeder::class);
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    $this->withoutVite();
});

function createCertificateTestContext(CertificateStatus $status = CertificateStatus::Pending): array
{
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    OrganizationUser::query()->create([
        'organization_id' => $organization->getKey(),
        'user_id' => $user->getKey(),
        'status' => OrganizationUserStatus::Active,
    ]);

    /** @var PermissionRegistrar $registrar */
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($organization->getKey());

    Role::findOrCreate('Administrator', 'web');
    $user->assignRole('Administrator');

    $registrar->setPermissionsTeamId(null);

    Session::put('organization_id', $organization->getKey());

    $design = Design::factory()
        ->for($organization)
        ->state([
            'creator_id' => $user->getKey(),
            'variables' => ['course', 'issued_on'],
        ])
        ->create();

    $campaign = Campaign::factory()
        ->for($organization)
        ->for($design)
        ->state([
            'creator_id' => $user->getKey(),
        ])
        ->create();

    $certificate = Certificate::factory()
        ->for($organization)
        ->for($design)
        ->state([
            'campaign_id' => $campaign->getKey(),
            'status' => $status,
        ])
        ->create();

    return [$user, $organization, $campaign, $certificate];
}

it('renders the certificate index with existing certificates', function (): void {
    [$user, , , $certificate] = createCertificateTestContext();
    actingAs($user);

    $this->get(route('certificates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('certificates/Index')
            ->has('certificates.data', fn (Assert $table) => $table
                ->where('0.id', $certificate->getKey())
                ->etc()
            )
        );
});

it('shows certificate details', function (): void {
    [$user, , , $certificate] = createCertificateTestContext(CertificateStatus::Issued);
    actingAs($user);

    $this->get(route('certificates.show', $certificate))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('certificates/Show')
            ->where('certificate.id', $certificate->getKey())
            ->where('certificate.status', CertificateStatus::Issued->value)
            ->where('can.download', true)
        );
});

it('revokes a certificate with a reason', function (): void {
    [$user, , , $certificate] = createCertificateTestContext(CertificateStatus::Issued);
    actingAs($user);

    $response = $this->post(route('certificates.revoke', $certificate), [
        'reason' => 'Incorrect recipient details',
    ]);

    $response->assertRedirect(route('certificates.show', $certificate));

    $certificate->refresh();

    expect($certificate->status)->toBe(CertificateStatus::Revoked)
        ->and($certificate->revocation_reason)->toBe('Incorrect recipient details');
});
