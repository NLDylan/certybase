<?php

use App\Enums\CampaignCompletionReason;
use App\Enums\CampaignStatus;
use App\Jobs\CheckCampaignCompletion;
use App\Jobs\ProcessBulkCertificateImport;
use App\Models\Campaign;
use App\Models\Design;
use App\Models\User;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

afterEach(fn () => Carbon::setTestNow());

it('creates a draft campaign with provided data', function () {
    $design = Design::factory()->active()->create();
    $service = app(CampaignService::class);

    $data = [
        'design_id' => $design->id,
        'name' => 'November Campaign',
        'description' => 'Issue certificates for November cohort',
        'variable_mapping' => [
            'recipient_name' => 'Name',
            'recipient_email' => 'Email',
            'variables' => ['course' => 'Course'],
        ],
        'start_date' => '2025-11-01',
        'end_date' => '2025-11-30',
        'certificate_limit' => 100,
    ];

    $campaign = $service->create($design->organization_id, $design->creator_id ?? User::factory()->create()->id, $data);

    expect($campaign)
        ->status->toBe(CampaignStatus::Draft)
        ->organization_id->toBe($design->organization_id)
        ->name->toBe('November Campaign')
        ->variable_mapping->toMatchArray($data['variable_mapping']);

    $this->assertDatabaseHas('campaigns', [
        'id' => $campaign->id,
        'organization_id' => $design->organization_id,
        'design_id' => $design->id,
        'status' => CampaignStatus::Draft->value,
    ]);
});

it('executes a draft campaign and dispatches completion check', function () {
    $campaign = Campaign::factory()->create([
        'status' => CampaignStatus::Draft,
        'start_date' => null,
    ]);

    Carbon::setTestNow('2025-11-07 10:00:00');
    Queue::fake();

    $service = app(CampaignService::class);
    $result = $service->execute($campaign->id);

    expect($result->status)->toBe(CampaignStatus::Active)
        ->and($result->start_date)->toBeInstanceOf(Carbon::class)
        ->and($result->start_date->toDateString())->toBe('2025-11-07');

    Queue::assertPushed(CheckCampaignCompletion::class, function (CheckCampaignCompletion $job) use ($campaign) {
        return $job->campaignId === $campaign->id;
    });
});

it('prevents executing a campaign that is not in draft status', function () {
    $campaign = Campaign::factory()->active()->create();

    $service = app(CampaignService::class);

    expect(fn () => $service->execute($campaign->id))
        ->toThrow(RuntimeException::class);
});

it('stores an import file and dispatches processing job', function () {
    Storage::fake('local');
    Queue::fake();

    $campaign = Campaign::factory()->create([
        'variable_mapping' => [
            'recipient_name' => 'name',
            'recipient_email' => 'email',
            'variables' => ['course' => 'course'],
        ],
    ]);

    $csvContent = <<<'CSV'
name,email,course
Jane Doe,jane@example.com,Leadership 101

CSV;

    $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

    $service = app(CampaignService::class);
    $rowCount = $service->importRecipients($campaign->id, $file);

    expect($rowCount)->toBe(1);

    Queue::assertPushed(ProcessBulkCertificateImport::class, function (ProcessBulkCertificateImport $job) use ($campaign) {
        return $job->campaignId === $campaign->id
            && Storage::disk($job->disk)->exists($job->storedPath);
    });
});

it('marks campaign as completed when the certificate limit is reached', function () {
    $campaign = Campaign::factory()->active()->create([
        'certificate_limit' => 10,
        'certificates_issued' => 10,
    ]);

    $service = app(CampaignService::class);
    $completed = $service->checkCompletion($campaign->id);

    expect($completed)->toBeTrue();

    $campaign->refresh();

    expect($campaign->status)->toBe(CampaignStatus::Completed)
        ->and($campaign->completion_reason)->toBe(CampaignCompletionReason::LimitReached);
});

it('marks campaign as completed when end date has passed', function () {
    Carbon::setTestNow('2025-11-07 12:00:00');

    $campaign = Campaign::factory()->active()->create([
        'certificate_limit' => null,
        'certificates_issued' => 0,
        'end_date' => '2025-11-05',
    ]);

    $service = app(CampaignService::class);
    $completed = $service->checkCompletion($campaign->id);

    expect($completed)->toBeTrue();

    $campaign->refresh();

    expect($campaign->status)->toBe(CampaignStatus::Completed)
        ->and($campaign->completion_reason)->toBe(CampaignCompletionReason::DateReached);
});
