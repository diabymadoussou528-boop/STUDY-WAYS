<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Trialing = 'trialing';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Cancelled => 'Annulé',
            self::Expired => 'Expiré',
            self::Trialing => 'Essai',
        };
    }
}
