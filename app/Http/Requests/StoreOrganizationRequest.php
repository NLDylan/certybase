<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', Rule::unique(Organization::class)],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'coc_number' => ['nullable', 'string', 'max:100'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', 'string', 'max:255'],
            'address_postal_code' => ['nullable', 'string', 'max:20'],
            'address_country' => ['nullable', 'string', 'size:2', 'uppercase'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
