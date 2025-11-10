<?php

namespace App\Services;

use App\Enums\CampaignCompletionReason;
use App\Enums\CampaignStatus;
use App\Enums\CertificateStatus;
use App\Jobs\CheckCampaignCompletion;
use App\Jobs\ProcessBulkCertificateImport;
use App\Models\Campaign;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CampaignService
{
    /**
     * Create a new campaign.
     */
    public function create(string $organizationId, string $userId, array $data): Campaign
    {
        return DB::transaction(function () use ($organizationId, $userId, $data) {
            $campaign = Campaign::create([
                'organization_id' => $organizationId,
                'creator_id' => $userId,
                'design_id' => $data['design_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'variable_mapping' => $data['variable_mapping'] ?? [],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'certificate_limit' => $data['certificate_limit'] ?? null,
                'status' => CampaignStatus::Draft,
            ]);

            return $campaign->refresh();
        });
    }

    /**
     * Execute a campaign (start certificate generation).
     */
    public function execute(string $campaignId): Campaign
    {
        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status !== CampaignStatus::Draft) {
            throw new RuntimeException('Campaign can only be executed from the draft state.');
        }

        $campaign->forceFill([
            'status' => CampaignStatus::Active,
            'completed_at' => null,
            'completion_reason' => null,
            'start_date' => $campaign->start_date ?? Carbon::now()->toDateString(),
        ])->save();

        CheckCampaignCompletion::dispatch($campaign->id);

        return $campaign->refresh();
    }

    /**
     * Manually finish an active campaign.
     */
    public function finish(string $campaignId): Campaign
    {
        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status !== CampaignStatus::Active) {
            throw new RuntimeException('Campaign can only be finished when active.');
        }

        $hasPendingCertificates = $campaign->certificates()
            ->where('status', CertificateStatus::Pending->value)
            ->exists();

        if ($hasPendingCertificates) {
            throw new RuntimeException('Campaign cannot be finished while certificates are still pending.');
        }

        $completionReason = $this->determineCompletionReason($campaign) ?? CampaignCompletionReason::Manual;

        $campaign->markAsCompleted($completionReason);

        return $campaign->refresh();
    }

    /**
     * Import recipients from CSV and create certificates.
     */
    public function importRecipients(string $campaignId, UploadedFile $csvFile): int
    {
        $campaign = Campaign::findOrFail($campaignId);

        $extension = $csvFile->getClientOriginalExtension() ?: 'csv';
        $fileName = Str::uuid().'.'.$extension;
        $directory = "campaign-imports/{$campaign->id}";
        $storedPath = Storage::disk('local')->putFileAs($directory, $csvFile, $fileName);

        $absolutePath = Storage::disk('local')->path($storedPath);
        $rowCount = $this->countCsvRows($absolutePath);

        ProcessBulkCertificateImport::dispatch(
            campaignId: $campaign->id,
            organizationId: $campaign->organization_id,
            storedPath: $storedPath,
            disk: 'local'
        );

        return $rowCount;
    }

    /**
     * Check if campaign should be completed.
     */
    public function checkCompletion(string $campaignId): bool
    {
        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status !== CampaignStatus::Active) {
            return false;
        }

        $reason = $this->determineCompletionReason($campaign);

        if (! $reason) {
            return false;
        }

        $campaign->markAsCompleted($reason);

        return true;
    }

    protected function determineCompletionReason(Campaign $campaign): ?CampaignCompletionReason
    {
        if ($campaign->certificate_limit !== null && $campaign->certificates_issued >= $campaign->certificate_limit) {
            return CampaignCompletionReason::LimitReached;
        }

        if ($campaign->end_date !== null && Carbon::now()->greaterThan($campaign->end_date->endOfDay())) {
            return CampaignCompletionReason::DateReached;
        }

        return null;
    }

    protected function countCsvRows(string $absolutePath): int
    {
        if (! is_readable($absolutePath)) {
            return 0;
        }

        $handle = fopen($absolutePath, 'rb');

        if (! $handle) {
            return 0;
        }

        $headerRead = false;
        $rowCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (! $headerRead) {
                $headerRead = true;

                continue;
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $rowCount++;
        }

        fclose($handle);

        return $rowCount;
    }

    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
