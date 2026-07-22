<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'reference',
    'user_id',
    'cargo_id',
    'guest_name',
    'guest_email',
    'shipping_phone',
    'shipping_line1',
    'shipping_line2',
    'shipping_city',
    'shipping_province',
    'shipping_postal_code',
    'shipping_country',
    'type',
    'status',
    'subtotal_cents',
    'shipping_cents',
    'total_cents',
    'currency',
    'notes',
    'placed_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => OrderType::class,
            'status' => OrderStatus::class,
            'subtotal_cents' => 'integer',
            'shipping_cents' => 'integer',
            'total_cents' => 'integer',
            'placed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (blank($order->reference)) {
                $order->reference = self::generateReference();
            }

            if ($order->placed_at === null) {
                $order->placed_at = now();
            }
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'OM-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (self::query()->where('reference', $reference)->exists());

        return $reference;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(Reservation::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (int) $this->items()->sum('line_total_cents');

        $this->forceFill([
            'subtotal_cents' => $subtotal,
            'total_cents' => $subtotal + (int) $this->shipping_cents,
        ])->save();
    }

    public function customerName(): string
    {
        return $this->guest_name ?: $this->user?->name ?: 'Client';
    }

    public function customerEmail(): ?string
    {
        return $this->guest_email ?: $this->user?->email;
    }

    public function shippingAddressFormatted(): ?string
    {
        if (! filled($this->shipping_line1) || ! filled($this->shipping_city)) {
            return null;
        }

        $parts = array_filter([
            $this->shipping_line1,
            $this->shipping_line2,
            trim(($this->shipping_city ?? '').' '.($this->shipping_province ?? '').' '.($this->shipping_postal_code ?? '')),
            $this->shipping_country === 'CA' ? 'Canada' : $this->shipping_country,
            $this->shipping_phone ? 'Tél. '.$this->shipping_phone : null,
        ], fn (?string $part): bool => filled($part));

        return implode("\n", $parts);
    }

    public function totalFormatted(): string
    {
        return number_format($this->total_cents / 100, 2, ',', ' ').' $';
    }

    public function subtotalFormatted(): string
    {
        return number_format($this->subtotal_cents / 100, 2, ',', ' ').' $';
    }
}
