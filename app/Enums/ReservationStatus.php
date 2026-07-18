<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Fulfilled = 'fulfilled';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmée',
            self::Cancelled => 'Annulée',
            self::Fulfilled => 'Honorée',
        };
    }
}
