<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Rescheduled = 'rescheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
