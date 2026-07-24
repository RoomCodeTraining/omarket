<?php

namespace App\Models;

use App\Enums\CustomRequestStatus;
use Database\Factories\CustomRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    public function items(): HasMany
    {
        return $this->hasMany(CustomRequestItem::class)->orderBy('sort_order')->orderBy('id');
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

    /**
     * @param  Collection<int, CustomRequestItem>|iterable<int, array{label: string, quantity: int, budget_cents?: int|null}>  $items
     * @return array{title: string, quantity: int, budget_cents: int|null, description: string}
     */
    public static function aggregatesFromItems(iterable $items, ?string $notes = null): array
    {
        $collection = Collection::make($items)->values();

        $labels = $collection->map(function (mixed $item): string {
            if ($item instanceof CustomRequestItem) {
                return $item->label;
            }

            return (string) ($item['label'] ?? '');
        })->filter()->values();

        $title = match (true) {
            $labels->isEmpty() => 'Course personnalisée',
            $labels->count() === 1 => $labels->first(),
            default => $labels->first().' (+'.($labels->count() - 1).')',
        };

        $quantity = (int) $collection->sum(function (mixed $item): int {
            if ($item instanceof CustomRequestItem) {
                return max(1, (int) $item->quantity);
            }

            return max(1, (int) ($item['quantity'] ?? 1));
        });

        $budgetParts = $collection->map(function (mixed $item): ?int {
            if ($item instanceof CustomRequestItem) {
                return $item->budget_cents;
            }

            return array_key_exists('budget_cents', $item) && $item['budget_cents'] !== null
                ? (int) $item['budget_cents']
                : null;
        });

        $hasBudget = $budgetParts->contains(fn (?int $value): bool => $value !== null);

        $notes = trim((string) $notes);
        $description = $notes !== ''
            ? $notes
            : $collection->map(function (mixed $item): string {
                if ($item instanceof CustomRequestItem) {
                    $label = $item->label;
                    $qty = $item->quantity;
                    $budget = $item->budgetFormatted();
                } else {
                    $label = (string) ($item['label'] ?? '');
                    $qty = max(1, (int) ($item['quantity'] ?? 1));
                    $budgetCents = $item['budget_cents'] ?? null;
                    $budget = $budgetCents !== null
                        ? number_format(((int) $budgetCents) / 100, 2, ',', ' ').' $'
                        : null;
                }

                return $budget !== null
                    ? "{$label} × {$qty} (budget {$budget})"
                    : "{$label} × {$qty}";
            })->implode("\n");

        return [
            'title' => $title,
            'quantity' => max(1, $quantity),
            'budget_cents' => $hasBudget ? (int) $budgetParts->sum(fn (?int $value): int => $value ?? 0) : null,
            'description' => $description,
        ];
    }

    public function refreshAggregatesFromItems(): void
    {
        $aggregates = self::aggregatesFromItems($this->items()->get());

        $this->forceFill([
            'title' => $aggregates['title'],
            'quantity' => $aggregates['quantity'],
            'budget_cents' => $aggregates['budget_cents'],
        ])->save();
    }
}
