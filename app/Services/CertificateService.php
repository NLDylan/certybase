<?php

namespace App\Services;

use App\Enums\CertificateStatus;
use App\Jobs\GenerateCertificatePDF;
use App\Models\Campaign;
use App\Models\Certificate;
use App\Models\Design;
use Illuminate\Support\Arr;
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

            $this->hydrateCertificatePayload($certificate, $campaign, $recipientData);

            GenerateCertificatePDF::dispatch($certificate->id);

            // TODO: Dispatch SendCertificateEmail job

            return $certificate;
        });
    }

    /**
     * Create multiple certificates in bulk.
     */
    public function bulkCreate(string $campaignId, array $recipientsArray): int
    {
        $campaign = Campaign::with('design')->findOrFail($campaignId);
        $created = 0;

        DB::transaction(function () use ($campaign, $recipientsArray, &$created) {
            foreach ($recipientsArray as $recipientData) {
                $certificate = Certificate::create([
                    'organization_id' => $campaign->organization_id,
                    'design_id' => $campaign->design_id,
                    'campaign_id' => $campaign->id,
                    'recipient_name' => $recipientData['recipient_name'],
                    'recipient_email' => $recipientData['recipient_email'],
                    'recipient_data' => $recipientData['recipient_data'] ?? [],
                    'status' => CertificateStatus::Pending,
                ]);

                $created++;

                $this->hydrateCertificatePayload($certificate, $campaign, $recipientData);

                GenerateCertificatePDF::dispatch($certificate->id);
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
        GenerateCertificatePDF::dispatch($certificateId);
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

    protected function hydrateCertificatePayload(Certificate $certificate, Campaign $campaign, array $recipientData): void
    {
        $design = $campaign->design ?? $certificate->design;

        if (! $design instanceof Design) {
            return;
        }

        $payload = $this->buildCertificatePayload($design, $certificate, $recipientData, $campaign);

        if ($payload === null) {
            return;
        }

        $certificate->forceFill([
            'certificate_data' => $payload,
        ])->save();
    }

    protected function buildCertificatePayload(Design $design, Certificate $certificate, array $recipientData, ?Campaign $campaign = null): ?array
    {
        $designData = $design->design_data;

        if (! is_array($designData) || $designData === []) {
            return null;
        }

        // Deep copy to avoid mutating the original design data structure
        $designData = json_decode(json_encode($designData, JSON_PRESERVE_ZERO_FRACTION), true) ?? [];

        $canvasWidth = (int) ($designData['width'] ?? 1684);
        $canvasHeight = (int) ($designData['height'] ?? 1191);
        $orientation = strtolower((string) Arr::get($design->settings, 'orientation', $canvasWidth >= $canvasHeight ? 'landscape' : 'portrait'));
        $backgroundColor = Arr::get($designData, 'background', '#ffffff');
        $backgroundImage = Arr::get($designData, 'backgroundImage.src');
        $fontFamily = Arr::get($design->settings, 'default_font_family', 'Inter, "Helvetica Neue", Helvetica, Arial, sans-serif');

        $values = $this->buildVariableValues($recipientData);

        $renderedDesign = $this->renderFabricDesign($designData, $values);
        $elements = $this->transformFabricObjects($renderedDesign['objects'] ?? [], $canvasWidth, $canvasHeight, $values);

        return [
            'layout' => array_filter([
                'width' => $canvasWidth,
                'height' => $canvasHeight,
                'orientation' => $orientation,
                'background_color' => $backgroundColor,
                'background_image' => $backgroundImage,
                'default_font_family' => $fontFamily,
            ], static fn ($value) => $value !== null),
            'elements' => $elements,
            'fabric' => $renderedDesign,
            'variables' => $values,
            'metadata' => array_filter([
                'certificate_id' => $certificate->getKey(),
                'campaign_id' => $campaign?->getKey(),
                'design_id' => $design->getKey(),
                'design_name' => $design->name,
                'generated_at' => now()->toIso8601String(),
            ]),
        ];
    }

    protected function buildVariableValues(array $recipientData): array
    {
        $values = [
            'recipient_name' => Arr::get($recipientData, 'recipient_name'),
            'recipient_email' => Arr::get($recipientData, 'recipient_email'),
        ];

        foreach (Arr::get($recipientData, 'recipient_data', []) as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $values[(string) $key] = $value === null ? null : (string) $value;
            }
        }

        return $values;
    }

    protected function renderFabricDesign(array $designData, array $values): array
    {
        if (! isset($designData['objects']) || ! is_array($designData['objects'])) {
            return $designData;
        }

        $designData['objects'] = array_map(function (array $object) use ($values) {
            if (! $this->isTextObject($object)) {
                return $object;
            }

            $template = Arr::get($object, 'template', Arr::get($object, 'text', ''));
            $rendered = $this->renderTemplate((string) $template, $values);

            $object['template'] = $template;
            $object['text'] = $rendered;
            $object['rendered_text'] = $rendered;

            return $object;
        }, $designData['objects']);

        return $designData;
    }

    protected function transformFabricObjects(array $objects, int $canvasWidth, int $canvasHeight, array $values): array
    {
        $elements = [];

        foreach ($objects as $index => $object) {
            if (! is_array($object)) {
                continue;
            }

            $elements[] = array_filter(
                $this->transformFabricObject($object, $canvasWidth, $canvasHeight, $values, $index),
                static fn ($value) => $value !== null
            );
        }

        return array_values(array_filter($elements));
    }

    protected function transformFabricObject(array $object, int $canvasWidth, int $canvasHeight, array $values, int $index): ?array
    {
        $type = strtolower((string) Arr::get($object, 'type', ''));
        $left = (float) Arr::get($object, 'left', 0);
        $top = (float) Arr::get($object, 'top', 0);
        $scaleX = (float) (Arr::get($object, 'scaleX', 1) ?: 1);
        $scaleY = (float) (Arr::get($object, 'scaleY', 1) ?: 1);
        $width = (float) Arr::get($object, 'width', 0) * $scaleX;
        $height = (float) Arr::get($object, 'height', 0) * $scaleY;

        if ($width <= 0) {
            $width = (float) Arr::get($object, 'fontSize', 16) * max(1, strlen((string) Arr::get($object, 'text', '')) / 2);
        }

        if ($height <= 0) {
            $height = (float) Arr::get($object, 'fontSize', 16) * 1.2;
        }

        $base = [
            'type' => $this->mapObjectType($type),
            'position' => [
                'x' => $left,
                'y' => $top,
            ],
            'size' => [
                'width' => $width,
                'height' => $height,
            ],
            'z_index' => $index + 1,
            'opacity' => (float) Arr::get($object, 'opacity', 1),
            'rotation' => (float) Arr::get($object, 'angle', 0),
            'background_color' => Arr::get($object, 'backgroundColor'),
        ];

        return match ($base['type']) {
            'text' => $this->transformTextObject($base, $object, $values),
            'image' => $this->transformImageObject($base, $object),
            'shape' => $this->transformShapeObject($base, $object),
            default => null,
        };
    }

    protected function transformTextObject(array $base, array $object, array $values): array
    {
        $template = Arr::get($object, 'template', Arr::get($object, 'text', ''));
        $content = $this->renderTemplate((string) $template, $values);

        $fontSize = (float) Arr::get($object, 'fontSize', 16);
        $charSpacing = (float) Arr::get($object, 'charSpacing', 0);
        $letterSpacing = $charSpacing !== 0 ? round(($charSpacing / 1000) * $fontSize, 2) : 0;

        $base['content'] = $content;
        $base['template'] = $template;
        $base['font'] = array_filter([
            'family' => Arr::get($object, 'fontFamily'),
            'size' => $fontSize,
            'weight' => Arr::get($object, 'fontWeight', 400),
            'style' => Arr::get($object, 'fontStyle'),
            'color' => Arr::get($object, 'fill', '#111827'),
            'line_height' => Arr::get($object, 'lineHeight', 1.2),
            'letter_spacing' => $letterSpacing,
        ], static fn ($value) => $value !== null);
        $base['text_align'] = Arr::get($object, 'textAlign', 'left');
        $base['transform'] = strtolower((string) Arr::get($object, 'textTransform', 'none'));

        return $base;
    }

    protected function transformImageObject(array $base, array $object): ?array
    {
        $src = Arr::get($object, 'src');

        if (! is_string($src) || $src === '') {
            return null;
        }

        $base['image_url'] = $src;

        return $base;
    }

    protected function transformShapeObject(array $base, array $object): array
    {
        $base['border'] = array_filter([
            'color' => Arr::get($object, 'stroke'),
            'width' => Arr::get($object, 'strokeWidth'),
            'style' => Arr::get($object, 'strokeDashArray') ? 'dashed' : 'solid',
        ], static fn ($value) => $value !== null);

        $base['border']['radius'] = Arr::get($object, 'rx') ?? Arr::get($object, 'ry');

        return $base;
    }

    protected function mapObjectType(string $type): string
    {
        return match ($type) {
            'image' => 'image',
            'rect', 'triangle', 'circle', 'line', 'ellipse', 'polygon', 'path' => 'shape',
            default => 'text',
        };
    }

    protected function isTextObject(array $object): bool
    {
        $type = strtolower((string) Arr::get($object, 'type', ''));

        return in_array($type, ['textbox', 'text', 'i-text'], true);
    }

    protected function renderTemplate(string $template, array $values): string
    {
        if ($template === '') {
            return '';
        }

        return preg_replace_callback('/{{\s*([\w\.-]+)\s*}}/', function ($matches) use ($values) {
            $key = $matches[1];
            $replacement = Arr::get($values, $key);

            if ($replacement === null || $replacement === '') {
                return $matches[0];
            }

            return (string) $replacement;
        }, $template) ?? $template;
    }
}
