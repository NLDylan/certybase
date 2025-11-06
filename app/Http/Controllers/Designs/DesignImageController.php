<?php

namespace App\Http\Controllers\Designs;

use App\Http\Controllers\Controller;
use App\Models\Design;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DesignImageController extends Controller
{
    /**
     * Upload an image file for a design.
     */
    public function upload(Request $request, Design $design): JsonResponse
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Ensure the design belongs to the organization
        if ($design->organization_id !== $organizationId) {
            abort(404);
        }

        $request->validate([
            'image' => ['required', 'image', 'max:10240'], // 10MB max
        ]);

        $media = $design
            ->addMediaFromRequest('image')
            ->toMediaCollection('canvas_images');

        // Return route URL instead of direct S3 URL so we can handle authentication
        return response()->json([
            'url' => route('designs.images.show', ['design' => $design->id, 'media' => $media->id]),
            'id' => $media->id,
        ]);
    }

    /**
     * Download an image from a URL (e.g., Unsplash) and store it for a design.
     */
    public function downloadFromUrl(Request $request, Design $design): JsonResponse
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Ensure the design belongs to the organization
        if ($design->organization_id !== $organizationId) {
            abort(404);
        }

        $request->validate([
            'url' => ['required', 'url', 'active_url'],
        ]);

        $url = $request->input('url');

        // Validate it's an image URL
        $imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg'];
        $isImageUrl = collect($imageExtensions)->contains(function ($ext) use ($url) {
            return Str::endsWith(strtolower($url), $ext);
        });

        if (! $isImageUrl && ! Str::contains($url, 'unsplash.com')) {
            return response()->json([
                'error' => 'Invalid image URL',
            ], 422);
        }

        try {
            $media = $design
                ->addMediaFromUrl($url)
                ->toMediaCollection('canvas_images');

            // Return route URL instead of direct S3 URL so we can handle authentication
            return response()->json([
                'url' => route('designs.images.show', ['design' => $design->id, 'media' => $media->id]),
                'id' => $media->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to download image: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Serve a media file for a design with proper authentication.
     */
    public function show(Request $request, Design $design, int $media)
    {
        $organizationId = $this->currentOrganizationIdOrFail();

        // Ensure the design belongs to the organization
        if ($design->organization_id !== $organizationId) {
            abort(404);
        }

        // Get all media collections for this design
        $mediaItem = $design->getMedia('canvas_images')
            ->firstWhere('id', $media);

        // If not found in canvas_images, check preview_image (for backwards compatibility)
        if (! $mediaItem) {
            $mediaItem = $design->getMedia('preview_image')
                ->firstWhere('id', $media);
        }

        if (! $mediaItem) {
            abort(404, 'Media not found');
        }

        try {
            // Get file content from storage disk
            $fileContent = Storage::disk($mediaItem->disk)->get($mediaItem->getPath());

            if ($fileContent === false || $fileContent === null) {
                abort(500, 'Failed to retrieve media file');
            }

            return response($fileContent, 200, [
                'Content-Type' => $mediaItem->mime_type ?? 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$mediaItem->file_name.'"',
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        } catch (\Exception $e) {
            Log::error('Error serving media file', [
                'media_id' => $media,
                'design_id' => $design->id,
                'media_path' => $mediaItem->getPath() ?? 'unknown',
                'disk' => $mediaItem->disk ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, 'Failed to serve media file: '.$e->getMessage());
        }
    }
}
