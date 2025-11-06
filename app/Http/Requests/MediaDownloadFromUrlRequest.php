<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MediaDownloadFromUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Controller does policy checks
    }

    public function rules(): array
    {
        $allowedModelTypes = array_keys((array) config('media.allowed_model_types', []));

        return [
            'model_type' => ['required', 'string', Rule::in($allowedModelTypes)],
            'model_id' => ['required', 'string'],
            'collection' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($allowedModelTypes) {
                    $modelType = (string) $this->input('model_type', '');
                    if ($modelType === '' || ! in_array($modelType, $allowedModelTypes, true)) {
                        $fail('Invalid model type.');
                        return;
                    }

                    $collections = (array) data_get(config('media.allowed_model_types'), $modelType . '.collections', []);
                    if (! in_array((string) $value, $collections, true)) {
                        $fail('Invalid collection for the specified model.');
                    }
                },
            ],
            'url' => ['required', 'url', 'active_url'],
        ];
    }
}


