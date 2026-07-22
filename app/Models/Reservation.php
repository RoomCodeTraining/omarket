<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cargo_item_id',
    'order_id',
    'user_id',
    'guest_name',
    'guest_email',
    'quantity',
    'unit_price_cents',
    'status',
    'reference',
])]
class Reservation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'status' => ReservationStatus::class,
        ];
    }

    public function cargoItem(): BelongsTo
    {
        return $this->belongsTo(CargoItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function totalCents(): int
    {
        return $this->quantity * $this->unit_price_cents;
    }

    public function totalFormatted(): string
    {
        return number_format($this->totalCents() / 100, 2, ',', ' ').' $';
    }
}
