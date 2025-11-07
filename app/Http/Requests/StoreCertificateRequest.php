<?php

namespace App\Http\Requests;

use App\Models\Certificate;
use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organizationId = session('organization_id');

        if (! $organizationId) {
            return false;
        }

        return $this->user()?->can('create', [Certificate::class, $organizationId]) ?? false;
    }

    public function rules(): array
    {
        return [
            'campaign_id' => ['required', 'uuid', 'exists:campaigns,id'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_email' => ['required', 'email', 'max:255'],
            'recipient_data' => ['nullable', 'array'],
        ];
    }
}
