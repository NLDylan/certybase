<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'status' => ['sometimes', 'required', 'string'],
        ];
    }
}
