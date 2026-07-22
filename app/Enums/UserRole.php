<?php

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Partner = 'partner';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::Partner => 'Partenaire',
            self::Admin => 'Administrateur',
        };
    }
}
