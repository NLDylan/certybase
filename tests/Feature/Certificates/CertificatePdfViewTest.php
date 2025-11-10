<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Services\CertificateService;
use Illuminate\Support\Facades\Bus;

it('renders the certificate pdf view with scaled styles and resolved asset urls', function (): void {
    Bus::fake();

    $campaign = Campaign::factory()->active()->create();

    $certificate = app(CertificateService::class)->create($campaign->getKey(), [
        'recipient_name' => 'PDF Recipient',
        'recipient_email' => 'pdf@example.com',
        'recipient_data' => [],
    ]);

    $certificate->refresh();

    $data = $certificate->certificate_data;

    $data['layout']['background_image'] = 'storage/backgrounds/sample.png';
    $data['elements'][] = [
        'type' => 'image',
        'position' => ['x' => 120, 'y' => 160],
        'size' => ['width' => 320, 'height' => 180],
        'z_index' => count($data['elements']) + 1,
        'opacity' => 1,
        'rotation' => 0,
        'background_color' => null,
        'image_url' => '/storage/signatures/sample.png',
    ];

    $certificate->forceFill([
        'certificate_data' => $data,
    ])->save();

    $freshCertificate = $certificate->fresh(['design']);

    $html = view('pdf.certificates.show', [
        'certificate' => $freshCertificate,
        'design' => $freshCertificate->design,
    ])->render();

    $layout = $data['layout'];
    $orientation = strtolower($layout['orientation'] ?? 'landscape');
    $pageWidthMm = $orientation === 'portrait' ? 210 : 297;
    $pageHeightMm = $orientation === 'portrait' ? 297 : 210;
    $canvasWidth = $layout['width'];
    $canvasHeight = $layout['height'];

    $mmToPx = 96 / 25.4;
    $scaleX = ($pageWidthMm * $mmToPx) / $canvasWidth;
    $scaleY = ($pageHeightMm * $mmToPx) / $canvasHeight;
    expect(preg_match('/background-image:\s*url\((["\']?)([^"\')]+)\1\)/', $html, $backgroundMatch))->toBe(1);
    expect(str_starts_with($backgroundMatch[2], 'http'))->toBeTrue();
    expect($backgroundMatch[2])->toEndWith('/storage/backgrounds/sample.png');

    expect(preg_match('/font-size:\s*([0-9.]+)px;/', $html, $fontMatch))->toBeGreaterThan(0);
    expect($fontMatch[1])->toBe('48');

    $formattedScaleX = number_format($scaleX, 6, '.', '');
    $formattedScaleY = number_format($scaleY, 6, '.', '');
    $formattedScaleX = $formattedScaleX === '0.000000' ? '0' : rtrim(rtrim($formattedScaleX, '0'), '.');
    $formattedScaleY = $formattedScaleY === '0.000000' ? '0' : rtrim(rtrim($formattedScaleY, '0'), '.');

    expect($html)->toContain('transform: scale('.$formattedScaleX.', '.$formattedScaleY.');');

    expect(preg_match('/left:\s*([0-9.]+)px;/', $html))->toBeGreaterThan(0);
    expect(preg_match('/top:\s*([0-9.]+)px;/', $html))->toBeGreaterThan(0);

    expect($html)->toContain('/storage/signatures/sample.png');
});

