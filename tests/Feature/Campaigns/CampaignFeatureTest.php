<?php

declare(strict_types=1);

use App\Enums\CampaignCompletionReason;
use App\Enums\CampaignStatus;
use App\Enums\OrganizationUserStatus;
use App\Jobs\ProcessBulkCertificateImport;
use App\Models\Campaign;
use App\Models\Design;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(Database\Seeders\PermissionsSeeder::class);
});

function createCampaignTestContext(CampaignStatus $status = CampaignStatus::Draft): array
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
            'status' => $status,
            'variable_mapping' => [
                'recipient_name' => 'full_name',
                'recipient_email' => 'email',
                'variables' => [
                    'course' => 'course',
                ],
            ],
        ])
        ->create();

    return [$user, $organization, $design, $campaign];
}

it('renders the campaign index with existing campaigns', function () {
    [$user] = createCampaignTestContext();
    actingAs($user);

    $this->get(route('campaigns.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('campaigns/Index')
            ->has('campaigns.data', 1)
        );
});

it('creates a campaign through the controller', function () {
    [$user, $organization, $design] = createCampaignTestContext();
    actingAs($user);

    $payload = [
        'name' => 'Launch Campaign',
        'description' => 'Launch certificates for cohort A',
        'design_id' => $design->getKey(),
        'start_date' => now()->toDateString(),
        'end_date' => now()->addWeek()->toDateString(),
        'certificate_limit' => 25,
        'variable_mapping' => [
            'recipient_name' => 'full_name',
            'recipient_email' => 'email',
            'variables' => [
                'course' => 'course_name',
            ],
        ],
    ];

    $response = $this->post(route('campaigns.store'), $payload);

    $response->assertRedirect();

    $this->assertDatabaseHas('campaigns', [
        'organization_id' => $organization->getKey(),
        'name' => 'Launch Campaign',
        'certificate_limit' => 25,
    ]);
});

it('transitions a draft campaign to active on execute', function () {
    [$user, , , $campaign] = createCampaignTestContext();
    actingAs($user);

    $this->post(route('campaigns.execute', $campaign))
        ->assertRedirect();

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Active);
});

it('allows an administrator to finish an active campaign manually', function () {
    [$user, , , $campaign] = createCampaignTestContext(CampaignStatus::Active);
    actingAs($user);

    $response = $this->post(route('campaigns.finish', $campaign));

    $response->assertRedirect(route('campaigns.show', $campaign));

    $campaign->refresh();

    expect($campaign->status)->toBe(CampaignStatus::Completed)
        ->and($campaign->completion_reason)->toBe(CampaignCompletionReason::Manual);
});

it('queues a csv import for processing', function () {
    [$user, , , $campaign] = createCampaignTestContext(CampaignStatus::Active);
    actingAs($user);

    Storage::fake('local');
    Queue::fake();

    $csvContent = "full_name,email\nJane Doe,jane@example.com\n";
    $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

    $response = $this->post(route('campaigns.import.store', $campaign), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('campaigns.show', $campaign));

    Queue::assertPushed(ProcessBulkCertificateImport::class, 1);
});
