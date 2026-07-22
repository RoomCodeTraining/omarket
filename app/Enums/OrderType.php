<?php

namespace App\Enums;

enum OrderType: string
{
    case Stock = 'stock';
    case Cargo = 'cargo';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Stock => 'Stock local',
            self::Cargo => 'Arrivage / cargo',
            self::Custom => 'Course personnalisée',
        };
    }
}
