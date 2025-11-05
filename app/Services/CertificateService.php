<?php

namespace App\Services;

use App\Enums\CertificateStatus;
use App\Models\Campaign;
use App\Models\Certificate;
use Illuminate\Support\Facades\DB;

class CertificateService
{
    /**
     * Create a single certificate.
     */
    public function create(string $campaignId, array $recipientData): Certificate
    {
        $campaign = Campaign::findOrFail($campaignId);

        return DB::transaction(function () use ($campaign, $recipientData) {
            // Create certificate
            $certificate = Certificate::create([
                'organization_id' => $campaign->organization_id,
                'design_id' => $campaign->design_id,
                'campaign_id' => $campaign->id,
                'recipient_name' => $recipientData['recipient_name'],
                'recipient_email' => $recipientData['recipient_email'],
                'recipient_data' => $recipientData['recipient_data'] ?? [],
                'status' => CertificateStatus::Pending,
            ]);

            // Increment campaign certificate count
            $campaign->incrementCertificatesIssued();

            // TODO: Dispatch GenerateCertificatePDF job
            // TODO: Dispatch SendCertificateEmail job

            return $certificate;
        });
    }

    /**
     * Create multiple certificates in bulk.
     */
    public function bulkCreate(string $campaignId, array $recipientsArray): int
    {
        $campaign = Campaign::findOrFail($campaignId);
        $created = 0;

        DB::transaction(function () use ($campaign, $recipientsArray, &$created) {
            foreach ($recipientsArray as $recipientData) {
                Certificate::create([
                    'organization_id' => $campaign->organization_id,
                    'design_id' => $campaign->design_id,
                    'campaign_id' => $campaign->id,
                    'recipient_name' => $recipientData['recipient_name'],
                    'recipient_email' => $recipientData['recipient_email'],
                    'recipient_data' => $recipientData['recipient_data'] ?? [],
                    'status' => CertificateStatus::Pending,
                ]);

                $created++;
            }

            // Update campaign certificate count
            $campaign->increment('certificates_issued', $created);
        });

        // TODO: Dispatch bulk PDF generation job
        // TODO: Dispatch bulk email job

        return $created;
    }

    /**
     * Generate PDF for a certificate.
     */
    public function generatePDF(string $certificateId): void
    {
        $certificate = Certificate::findOrFail($certificateId);

        // TODO: Implement PDF generation from certificate_data
        // This should be queued and stored in the certificate_pdf collection
    }

    /**
     * Revoke a certificate.
     */
    public function revoke(string $certificateId, ?string $reason = null): Certificate
    {
        $certificate = Certificate::findOrFail($certificateId);
        $certificate->revoke($reason);

        return $certificate;
    }

    /**
     * Verify a certificate by token.
     */
    public function verify(string $verificationToken): ?Certificate
    {
        return Certificate::where('verification_token', $verificationToken)
            ->where('status', CertificateStatus::Issued)
            ->with(['organization', 'design'])
            ->first();
    }
}

