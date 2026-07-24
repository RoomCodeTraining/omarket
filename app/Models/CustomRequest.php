<?php

namespace App\Models;

use App\Enums\CustomRequestStatus;
use Database\Factories\CustomRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'guest_name',
    'guest_email',
    'title',
    'description',
    'quantity',
    'budget_cents',
    'has_supplier',
    'supplier_name',
    'supplier_contact',
    'cargo_id',
    'status',
    'validated_at',
    'cargo_assigned_at',
])]
class CustomRequest extends Model
{
    /** @use HasFactory<CustomRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'budget_cents' => 'integer',
            'has_supplier' => 'boolean',
            'status' => CustomRequestStatus::class,
            'validated_at' => 'datetime',
            'cargo_assigned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function isValidated(): bool
    {
        return $this->validated_at !== null;
    }

    public function supplierLabel(): string
    {
        if (! $this->has_supplier) {
            return 'Aucun fournisseur précisé';
        }

        $parts = array_filter([
            $this->supplier_name,
            $this->supplier_contact,
        ]);

        return $parts !== [] ? implode(' — ', $parts) : 'Fournisseur indiqué (détails à préciser)';
    }
}
