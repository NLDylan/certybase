<?php

declare(strict_types=1);

use App\Enums\CertificateStatus;
use App\Enums\OrganizationUserStatus;
use App\Jobs\GenerateCertificatePDF;
use App\Models\Campaign;
use App\Models\Certificate;
use App\Models\Design;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Bus;
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
            ->where('certificate.has_pdf', false)
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

it('dispatches pdf generation when creating a certificate', function (): void {
    Bus::fake();

    $campaign = Campaign::factory()->active()->create();

    $certificate = app(CertificateService::class)->create($campaign->getKey(), [
        'recipient_name' => 'Test Recipient',
        'recipient_email' => 'test@example.com',
        'recipient_data' => ['course' => 'Laravel Fundamentals'],
    ]);

    Bus::assertDispatched(GenerateCertificatePDF::class);

    $certificate->refresh();

    expect($certificate->certificate_data)->toBeArray()
        ->and(data_get($certificate->certificate_data, 'elements.0.content'))->toContain('Test Recipient');
});

it('dispatches pdf generation for each bulk certificate', function (): void {
    Bus::fake();

    $campaign = Campaign::factory()->active()->create();

    app(CertificateService::class)->bulkCreate($campaign->getKey(), [
        [
            'recipient_name' => 'First Recipient',
            'recipient_email' => 'first@example.com',
            'recipient_data' => ['course' => 'Vue Basics'],
        ],
        [
            'recipient_name' => 'Second Recipient',
            'recipient_email' => 'second@example.com',
            'recipient_data' => ['course' => 'Tailwind Intro'],
        ],
    ]);

    Bus::assertDispatchedTimes(GenerateCertificatePDF::class, 2);

    $first = Certificate::where('recipient_name', 'First Recipient')->first();
    $second = Certificate::where('recipient_name', 'Second Recipient')->first();

    expect($first)->not->toBeNull()
        ->and($first->certificate_data)->toBeArray()
        ->and(data_get($first->certificate_data, 'variables.recipient_name'))->toBe('First Recipient');

    expect($second)->not->toBeNull()
        ->and($second->certificate_data)->toBeArray()
        ->and(data_get($second->certificate_data, 'variables.recipient_name'))->toBe('Second Recipient');
});

it('queues pdf generation when downloading a certificate without media', function (): void {
    [$user, , , $certificate] = createCertificateTestContext(CertificateStatus::Issued);
    actingAs($user);

    Bus::fake();

    $response = $this->get(route('certificates.download', $certificate));

    $response->assertStatus(409);

    Bus::assertDispatched(GenerateCertificatePDF::class, function (GenerateCertificatePDF $job) use ($certificate) {
        return $job->certificateId === $certificate->getKey();
    });
});

it('stores a rendered snapshot in certificate data', function (): void {
    Bus::fake();

    $organization = Organization::factory()->create();
    $designer = User::factory()->create();

    $design = Design::factory()
        ->for($organization)
        ->state([
            'creator_id' => $designer->getKey(),
        ])
        ->create();

    $campaign = Campaign::factory()
        ->for($organization)
        ->for($design)
        ->active()
        ->create();

    $certificate = app(CertificateService::class)->create($campaign->getKey(), [
        'recipient_name' => 'Snapshot Recipient',
        'recipient_email' => 'snapshot@example.com',
        'recipient_data' => ['course' => 'Snapshot Course'],
    ]);

    $certificate->refresh();

    $content = data_get($certificate->certificate_data, 'elements.0.content');
    $variables = data_get($certificate->certificate_data, 'variables');

    expect($content)->toBeString()
        ->and($content)->toContain('Snapshot Recipient')
        ->and($variables)->toMatchArray([
            'recipient_name' => 'Snapshot Recipient',
            'recipient_email' => 'snapshot@example.com',
            'course' => 'Snapshot Course',
        ]);
});
