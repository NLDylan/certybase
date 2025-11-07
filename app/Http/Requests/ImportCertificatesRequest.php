<?php

namespace App\Http\Requests;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;

class ImportCertificatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        if (! $campaign instanceof Campaign) {
            return false;
        }

        return $this->user()?->can('execute', $campaign) ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimetypes:text/plain,text/csv,text/tsv', 'mimes:csv,txt'],
        ];
    }
}
