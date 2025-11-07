<?php

namespace App\Http\Requests;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        if ($campaign instanceof Campaign) {
            return $this->user()?->can('update', $campaign) ?? false;
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'variable_mapping' => ['sometimes', 'nullable', 'array'],
            'variable_mapping.recipient_name' => ['nullable', 'string'],
            'variable_mapping.recipient_email' => ['nullable', 'string'],
            'variable_mapping.variables' => ['nullable', 'array'],
            'variable_mapping.variables.*' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(CampaignStatus::class)],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'certificate_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
