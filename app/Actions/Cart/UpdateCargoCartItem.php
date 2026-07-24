<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Models\CargoItem;
use App\Support\CargoCart;
use Illuminate\Validation\ValidationException;

final class UpdateCargoCartItem
{
    public function handle(int $cargoItemId, int $quantity): void
    {
        $state = CargoCart::state();

        if (! isset($state['items'][$cargoItemId])) {
            throw ValidationException::withMessages([
                'cargo_item_id' => 'Cet article n’est pas dans le panier arrivage.',
            ]);
        }

        if ($quantity <= 0) {
            unset($state['items'][$cargoItemId]);

            if ($state['items'] === []) {
                CargoCart::clear();

                return;
            }

            CargoCart::put($state);

            return;
        }

        $item = CargoItem::query()->findOrFail($cargoItemId);

        if ($quantity > $item->quantityRemaining()) {
            throw ValidationException::withMessages([
                'quantity' => "Quantité insuffisante (reste : {$item->quantityRemaining()}).",
            ]);
        }

        $state['items'][$cargoItemId]['quantity'] = $quantity;
        CargoCart::put($state);
    }
}
