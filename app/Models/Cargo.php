<?php

namespace App\Models;

use App\Enums\CargoStatus;
use Database\Factories\CargoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'code',
    'name',
    'origin',
    'destination',
    'status',
    'departure_at',
    'estimated_arrival_at',
    'notes',
])]
class Cargo extends Model
{
    /** @use HasFactory<CargoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => CargoStatus::class,
            'departure_at' => 'date',
            'estimated_arrival_at' => 'date',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CargoItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reservations(): HasManyThrough
    {
        return $this->hasManyThrough(Reservation::class, CargoItem::class);
    }

    public function scopeOpenForReservation(Builder $query): Builder
    {
        return $query->where('status', CargoStatus::Open);
    }

    /**
     * Partenaires : produits uniquement tant que le cargo est en mer.
     *
     * @param  Builder<Cargo>  $query
     * @return Builder<Cargo>
     */
    public function scopeAcceptsPartnerProducts(Builder $query): Builder
    {
        return $query->where('status', CargoStatus::InTransit);
    }

    public function acceptsPartnerProducts(): bool
    {
        return $this->status === CargoStatus::InTransit;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function routeLabel(): string
    {
        return "{$this->origin} → {$this->destination}";
    }
}
