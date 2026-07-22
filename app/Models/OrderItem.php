<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'product_id',
    'product_name',
    'product_unit',
    'unit_price_cents',
    'quantity',
    'line_total_cents',
    'cargo_item_id',
    'quote_id',
])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'line_total_cents' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (OrderItem $item): void {
            $item->line_total_cents = max(0, (int) $item->unit_price_cents) * max(0, (int) $item->quantity);
        });

        static::saved(function (OrderItem $item): void {
            $item->order?->recalculateTotals();
        });

        static::deleted(function (OrderItem $item): void {
            $item->order?->recalculateTotals();
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cargoItem(): BelongsTo
    {
        return $this->belongsTo(CargoItem::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function lineTotalFormatted(): string
    {
        return number_format($this->line_total_cents / 100, 2, ',', ' ').' $';
    }
}
