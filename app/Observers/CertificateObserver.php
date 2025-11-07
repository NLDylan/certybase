<?php

namespace App\Observers;

use App\Models\Certificate;
use Illuminate\Support\Str;

class CertificateObserver
{
    public function creating(Certificate $certificate): void
    {
        if ($certificate->verification_token !== null && $certificate->verification_token !== '') {
            return;
        }

        $certificate->verification_token = Str::random(64);
    }
}
