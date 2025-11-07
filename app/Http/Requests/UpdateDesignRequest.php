<?php

namespace App\Http\Requests;

use App\Enums\DesignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'design_data' => ['nullable', 'array'],
            'variables' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'status' => ['sometimes', 'required', Rule::enum(DesignStatus::class)],
        ];
    }
}
