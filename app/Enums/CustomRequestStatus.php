<?php

namespace App\Enums;

enum CustomRequestStatus: string
{
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Quoted = 'quoted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Soumise',
            self::InReview => 'En étude',
            self::Quoted => 'Devis envoyé',
            self::Accepted => 'Acceptée',
            self::Rejected => 'Refusée',
            self::Cancelled => 'Annulée',
        };
    }
}
