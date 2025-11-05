<?php

namespace App\Services;

use App\Enums\CampaignCompletionReason;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    /**
     * Create a new campaign.
     */
    public function create(string $organizationId, string $userId, array $data): Campaign
    {
        return Campaign::create([
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
    }

    /**
     * Execute a campaign (start certificate generation).
     */
    public function execute(string $campaignId): Campaign
    {
        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status !== CampaignStatus::Draft) {
            throw new \Exception('Campaign is not in draft status.');
        }

        $campaign->update([
            'status' => CampaignStatus::Active,
        ]);

        // TODO: Dispatch initial certificate generation if needed
        // TODO: Check completion conditions

        return $campaign;
    }

    /**
     * Import recipients from CSV and create certificates.
     */
    public function importRecipients(string $campaignId, $csvFile): int
    {
        $campaign = Campaign::findOrFail($campaignId);

        // TODO: Parse CSV file
        // TODO: Map CSV columns to variables based on variable_mapping
        // TODO: Queue ProcessBulkCertificateImport job

        return 0;
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

        $completed = false;
        $reason = null;

        // Check certificate limit
        if ($campaign->certificate_limit && $campaign->certificates_issued >= $campaign->certificate_limit) {
            $completed = true;
            $reason = CampaignCompletionReason::LimitReached;
        }

        // Check end date
        if ($campaign->end_date && now()->isAfter($campaign->end_date)) {
            $completed = true;
            $reason = CampaignCompletionReason::DateReached;
        }

        if ($completed && $reason) {
            $campaign->markAsCompleted($reason);
        }

        return $completed;
    }
}

