<?php

namespace App\Enums;

enum CampaignCompletionReason: string
{
    case LimitReached = 'limit_reached';
    case DateReached = 'date_reached';
    case Manual = 'manual';
}
