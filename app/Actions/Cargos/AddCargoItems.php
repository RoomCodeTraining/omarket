<?php

declare(strict_types=1);

namespace App\Actions\Cargos;

use App\Models\Cargo;
use App\Models\CargoItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AddCargoItems
{
    /**
     * @param  list<array{product_id: int, quantity_available: int, unit_price_cents: int}>  $lines
     * @return list<CargoItem>
     */
    public function handle(Cargo $cargo, array $lines): array
    {
        if ($lines === []) {
            throw ValidationException::withMessages([
                'items' => 'Ajoutez au moins une ligne produit.',
            ]);
        }

        $productIds = collect($lines)->pluck('product_id')->map(fn ($id) => (int) $id);

        if ($productIds->unique()->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Un même produit ne peut apparaître qu’une fois dans la liste.',
            ]);
        }

        return DB::transaction(function () use ($cargo, $lines) {
            $created = [];

            foreach ($lines as $line) {
                $productId = (int) $line['product_id'];
                $quantity = max(0, (int) $line['quantity_available']);
                $priceCents = max(0, (int) $line['unit_price_cents']);

                $item = CargoItem::query()->firstOrNew([
                    'cargo_id' => $cargo->id,
                    'product_id' => $productId,
                ]);

                $reserved = (int) ($item->quantity_reserved ?? 0);

                $item->fill([
                    'quantity_available' => max($quantity, $reserved),
                    'quantity_reserved' => $reserved,
                    'unit_price_cents' => $priceCents,
                ])->save();

                $created[] = $item->refresh();
            }

            return $created;
        });
    }
}
