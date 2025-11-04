<?php

namespace App\Enums;

enum OrganizationUserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}
