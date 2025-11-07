<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\CampaignService;
use App\Services\CertificateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ProcessBulkCertificateImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $campaignId,
        public string $organizationId,
        public string $storedPath,
        public string $disk = 'local'
    ) {}

    public function handle(CertificateService $certificateService, CampaignService $campaignService): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (! $campaign || $campaign->organization_id !== $this->organizationId) {
            $this->cleanup();

            return;
        }

        $rows = $this->extractRows($campaign);

        if (empty($rows)) {
            $this->cleanup();
            $campaignService->checkCompletion($campaign->id);

            return;
        }

        if ($campaign->certificate_limit !== null) {
            $remaining = max($campaign->certificate_limit - $campaign->certificates_issued, 0);

            if ($remaining === 0) {
                $this->cleanup();
                $campaignService->checkCompletion($campaign->id);

                return;
            }

            $rows = array_slice($rows, 0, $remaining);
        }

        $created = $certificateService->bulkCreate($campaign->id, $rows);

        $this->cleanup();

        if ($created > 0) {
            $campaignService->checkCompletion($campaign->id);
        }
    }

    protected function extractRows(Campaign $campaign): array
    {
        $fullPath = Storage::disk($this->disk)->path($this->storedPath);

        if (! is_readable($fullPath)) {
            return [];
        }

        $handle = fopen($fullPath, 'rb');

        if (! $handle) {
            return [];
        }

        $headers = null;
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map(static fn ($value) => trim((string) $value), $row);

                continue;
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $assoc = $this->combineRow($headers, $row);
            $payload = $this->mapRowToRecipientData($assoc, $campaign->variable_mapping ?? []);

            if ($payload !== null) {
                $rows[] = $payload;
            }
        }

        fclose($handle);

        return $rows;
    }

    protected function combineRow(array $headers, array $values): array
    {
        $combined = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $combined[$header] = trim((string) Arr::get($values, $index, ''));
        }

        return $combined;
    }

    protected function mapRowToRecipientData(array $row, array $mapping): ?array
    {
        $recipientNameColumn = $mapping['recipient_name'] ?? 'recipient_name';
        $recipientEmailColumn = $mapping['recipient_email'] ?? 'recipient_email';
        $variableColumns = $mapping['variables'] ?? [];

        $recipientName = $row[$recipientNameColumn] ?? null;
        $recipientEmail = $row[$recipientEmailColumn] ?? null;

        if (! $recipientName || ! $recipientEmail) {
            return null;
        }

        $recipientData = [];

        foreach ($variableColumns as $variableKey => $columnName) {
            if (array_key_exists($columnName, $row)) {
                $recipientData[$variableKey] = $row[$columnName];
            }
        }

        return [
            'recipient_name' => $recipientName,
            'recipient_email' => $recipientEmail,
            'recipient_data' => $recipientData,
        ];
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

    protected function cleanup(): void
    {
        Storage::disk($this->disk)->delete($this->storedPath);
    }
}
