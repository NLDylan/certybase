<?php

namespace App\Http\Requests;

use App\Enums\DesignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'design_data' => ['nullable', 'array'],
            'variables' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'status' => ['nullable', Rule::enum(DesignStatus::class)],
        ];
    }
}

