<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get organization from session (set by OrganizationContext middleware)
        $organizationId = $this->session()->get('organization_id');

        if (! $organizationId) {
            return false;
        }

        $organization = Organization::find($organizationId);

        return $organization && $this->user()->can('update', $organization);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get organization ID from session
        $organizationId = $this->session()->get('organization_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(Organization::class)->ignore($organizationId),
            ],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
