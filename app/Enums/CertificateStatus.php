<?php

namespace App\Enums;

enum CertificateStatus: string
{
    case Pending = 'pending';
    case Issued = 'issued';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
