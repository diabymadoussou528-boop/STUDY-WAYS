<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Cancelled => 'Annulé',
            self::Completed => 'Complété',
        };
    }
}
