<?php

namespace App\Enums;

enum CargoStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Open => 'Ouvert aux réservations',
            self::Closed => 'Réservations fermées',
            self::InTransit => 'En mer',
            self::Arrived => 'Arrivé au Canada',
            self::Completed => 'Terminé',
        };
    }
}
