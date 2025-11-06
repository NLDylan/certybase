<?php

declare(strict_types=1);

namespace App\Support\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{
    /**
     * Get the path for the given media, relative to the root storage path.
     * Format: {model-name}/{uuid}/{collection-name}/
     */
    public function getPath(Media $media): string
    {
        $modelName = $this->getModelName($media);
        $modelId = $media->model?->id ?? 'orphaned';
        $collectionName = $media->collection_name;

        return "{$modelName}/{$modelId}/{$collectionName}/";
    }

    /**
     * Get the path for conversions of the given media, relative to the root storage path.
     * Format: {model-name}/{uuid}/{collection-name}/conversions/
     */
    public function getPathForConversions(Media $media): string
    {
        $modelName = $this->getModelName($media);
        $modelId = $media->model?->id ?? 'orphaned';
        $collectionName = $media->collection_name;

        return "{$modelName}/{$modelId}/{$collectionName}/conversions/";
    }

    /**
     * Get the path for responsive images of the given media, relative to the root storage path.
     * Format: {model-name}/{uuid}/{collection-name}/responsive-images/
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        $modelName = $this->getModelName($media);
        $modelId = $media->model?->id ?? 'orphaned';
        $collectionName = $media->collection_name;

        return "{$modelName}/{$modelId}/{$collectionName}/responsive-images/";
    }

    /**
     * Get the model name in kebab-case format.
     */
    private function getModelName(Media $media): string
    {
        $modelClass = $media->model_type;
        $modelName = class_basename($modelClass);

        return str($modelName)->kebab()->toString();
    }
}
