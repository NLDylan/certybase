<?php

namespace App\Jobs;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Spatie\MediaLibrary\Support\TemporaryDirectory;

class GenerateCertificatePDF implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $certificateId)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $certificate = Certificate::with(['design', 'organization'])->findOrFail($this->certificateId);

        $layout = $certificate->certificate_data['layout'] ?? [];
        $orientation = strtolower($layout['orientation'] ?? 'landscape');

        $html = view('pdf.certificates.show', [
            'certificate' => $certificate,
            'design' => $certificate->design,
        ])->render();

        $temporaryDirectory = TemporaryDirectory::create();

        $temporaryPath = $temporaryDirectory->path(
            Str::uuid()->toString().DIRECTORY_SEPARATOR.'certificate.pdf'
        );

        try {
            $browsershot = Browsershot::html($html)
                ->waitUntilNetworkIdle()
                ->timeout((int) config('browsershot.timeout', 120))
                ->showBackground()
                ->format('A4')
                ->margins(0, 0, 0, 0);

            if ($nodePath = config('browsershot.node_path')) {
                $browsershot->setNodeBinary($nodePath);
            }

            if ($npmPath = config('browsershot.npm_path')) {
                $browsershot->setNpmBinary($npmPath);
            }

            if ($chromePath = config('browsershot.chrome_path')) {
                $browsershot->setChromePath($chromePath);
            }

            $chromiumArguments = config('browsershot.arguments', []);

            if ($chromiumArguments !== []) {
                $browsershot->addChromiumArguments($chromiumArguments);
            }

            if ($orientation === 'portrait') {
                $browsershot->setOption('landscape', false);
            } else {
                $browsershot->landscape();
            }

            $browsershot->save($temporaryPath);

            $certificate->clearMediaCollection('certificate_pdf');
            $certificate
                ->addMedia($temporaryPath)
                ->usingFileName('certificate-'.$certificate->id.'.pdf')
                ->withCustomProperties([
                    'generated_at' => now()->toIso8601String(),
                    'orientation' => $orientation,
                ])
                ->toMediaCollection('certificate_pdf');

            if (! $certificate->isIssued()) {
                $certificate->issue();
            }
        } catch (\Throwable $exception) {
            Log::error('Failed to generate certificate PDF', [
                'certificate_id' => $this->certificateId,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            $temporaryDirectory->delete();
        }
    }
}
