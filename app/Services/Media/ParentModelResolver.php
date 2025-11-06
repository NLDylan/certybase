<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ParentModelResolver
{
    /**
     * Resolve a parent model by validated model type and id, ensuring org scope when present.
     */
    public function resolve(string $modelType, string $modelId, string $organizationId): Model
    {
        $map = (array) config('media.allowed_model_types', []);

        if (! array_key_exists($modelType, $map)) {
            abort(422, 'Unsupported model type.');
        }

        /** @var class-string<Model> $class */
        $class = Arr::get($map, $modelType . '.class');
        if (! is_string($class) || $class === '') {
            abort(500, 'Model type mapping misconfigured.');
        }

        /** @var Model|null $model */
        $model = $class::query()->find($modelId);
        if ($model === null) {
            abort(404);
        }

        if (property_exists($model, 'organization_id') && (string) $model->organization_id !== (string) $organizationId) {
            abort(404);
        }

        return $model;
    }
}


