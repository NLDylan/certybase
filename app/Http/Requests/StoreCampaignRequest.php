<?php

namespace App\Http\Requests;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organizationId = session('organization_id');

        if (! $organizationId || ! $this->user()) {
            return false;
        }

        return $this->user()->can('create', [Campaign::class, $organizationId]);
    }

    public function rules(): array
    {
        $organizationId = session('organization_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'design_id' => [
                'required',
                'uuid',
                Rule::exists('designs', 'id')->where('organization_id', $organizationId),
            ],
            'variable_mapping' => ['nullable', 'array'],
            'variable_mapping.recipient_name' => ['nullable', 'string'],
            'variable_mapping.recipient_email' => ['nullable', 'string'],
            'variable_mapping.variables' => ['nullable', 'array'],
            'variable_mapping.variables.*' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'certificate_limit' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
