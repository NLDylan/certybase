<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaDownloadFromUrlRequest;
use App\Http\Requests\MediaUploadRequest;
use App\Services\Media\ParentModelResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class MediaController extends Controller
{
    public function __construct(private ParentModelResolver $parentModelResolver)
    {
    }

    /**
     * GET /media/{media} → stream a media item after policy check
     */
    public function show(Request $request, int $media)
    {
        $mediaItem = SpatieMedia::query()->findOrFail($media);

        $parentModel = $mediaItem->model;

        if ($parentModel === null) {
            abort(404);
        }

        // Organization scope check when applicable
        $organizationId = $this->currentOrganizationIdOrFail();
        if (isset($parentModel->organization_id) && (string) $parentModel->organization_id !== (string) $organizationId) {
            abort(404);
        }

        Gate::authorize('view', $parentModel);

        // Prefer presigned/temporary URLs when available
        try {
            $disk = $mediaItem->disk;
            $path = $mediaItem->getPath();

            // Attempt to generate a temporary URL (works for S3-like drivers)
            if (method_exists(Storage::disk($disk), 'temporaryUrl')) {
                $temporaryUrl = Storage::disk($disk)->temporaryUrl($path, now()->addMinutes((int) config('media-library.temporary_url_default_lifetime', 5)));
                // Redirect the client directly to the presigned URL so images load in-place
                return redirect()->away($temporaryUrl);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed generating temporary URL for media; will stream instead', [
                'media_id' => $mediaItem->id,
                'disk' => $mediaItem->disk,
                'path' => $mediaItem->getPath(),
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback: stream the file inline from the configured disk
        try {
            return Storage::disk($mediaItem->disk)->response(
                $mediaItem->getPath(),
                $mediaItem->file_name,
                [
                    'Cache-Control' => 'public, max-age=31536000',
                ],
                'inline'
            );
        } catch (\Throwable $e) {
            Log::error('Error serving media file', [
                'media_id' => $media,
                'media_path' => $mediaItem->getPath() ?? 'unknown',
                'disk' => $mediaItem->disk ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Failed to serve media file: ' . $e->getMessage());
        }
    }

    /**
     * POST /media → upload to collection
     */
    public function store(MediaUploadRequest $request): JsonResponse
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        $modelType = (string) $request->input('model_type');
        $modelId = (string) $request->input('model_id');
        $collection = (string) $request->input('collection');

        $parent = $this->parentModelResolver->resolve($modelType, $modelId, $organizationId);

        Gate::authorize('update', $parent);

        $media = $parent
            ->addMediaFromRequest('file')
            ->toMediaCollection($collection);

        return response()->json([
            'id' => $media->id,
            'url' => route('media.show', ['media' => $media->id]),
        ]);
    }

    /**
     * POST /media/from-url → download by URL to collection
     */
    public function storeFromUrl(MediaDownloadFromUrlRequest $request): JsonResponse
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        $modelType = (string) $request->input('model_type');
        $modelId = (string) $request->input('model_id');
        $collection = (string) $request->input('collection');
        $url = (string) $request->input('url');

        $parent = $this->parentModelResolver->resolve($modelType, $modelId, $organizationId);

        Gate::authorize('update', $parent);

        try {
            $media = $parent
                ->addMediaFromUrl($url)
                ->toMediaCollection($collection);

            return response()->json([
                'id' => $media->id,
                'url' => route('media.show', ['media' => $media->id]),
            ]);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to download media: ' . $e->getMessage(),
            ], 500);
        }
    }
}
