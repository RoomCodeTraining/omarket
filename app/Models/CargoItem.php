<?php

namespace App\Models;

use Database\Factories\CargoItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'cargo_id',
    'product_id',
    'quantity_available',
    'quantity_reserved',
    'unit_price_cents',
])]
class CargoItem extends Model
{
    /** @use HasFactory<CargoItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity_available' => 'integer',
            'quantity_reserved' => 'integer',
            'unit_price_cents' => 'integer',
        ];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function quantityRemaining(): int
    {
        return max(0, $this->quantity_available - $this->quantity_reserved);
    }

    public function unitPriceFormatted(): string
    {
        return number_format($this->unit_price_cents / 100, 2, ',', ' ').' $';
    }
}
