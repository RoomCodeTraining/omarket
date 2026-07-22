<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaymentConfirmed = 'payment_confirmed';
    case Preparing = 'preparing';
    case Loaded = 'loaded';
    case AtSea = 'at_sea';
    case ArrivedCanada = 'arrived_canada';
    case Available = 'available';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Paiement en attente',
            self::PaymentConfirmed => 'Paiement confirmé',
            self::Preparing => 'En préparation',
            self::Loaded => 'Chargé',
            self::AtSea => 'En mer',
            self::ArrivedCanada => 'Arrivé au Canada',
            self::Available => 'Disponible',
            self::Delivered => 'Livré',
            self::Cancelled => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::PaymentConfirmed => 'info',
            self::Preparing => 'primary',
            self::Loaded => 'primary',
            self::AtSea => 'info',
            self::ArrivedCanada => 'success',
            self::Available => 'success',
            self::Delivered => 'gray',
            self::Cancelled => 'danger',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::PaymentConfirmed, self::Cancelled],
            self::PaymentConfirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [self::Loaded, self::Cancelled],
            self::Loaded => [self::AtSea, self::Cancelled],
            self::AtSea => [self::ArrivedCanada],
            self::ArrivedCanada => [self::Available],
            self::Available => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
