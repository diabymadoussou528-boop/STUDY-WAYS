<?php

namespace App\Enums;

enum AdminActionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
