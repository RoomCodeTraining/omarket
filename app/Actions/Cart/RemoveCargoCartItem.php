<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Support\CargoCart;
use Illuminate\Validation\ValidationException;

final class RemoveCargoCartItem
{
    public function handle(int $cargoItemId): void
    {
        $state = CargoCart::state();

        if (! isset($state['items'][$cargoItemId])) {
            throw ValidationException::withMessages([
                'cargo_item_id' => 'Cet article n’est pas dans le panier arrivage.',
            ]);
        }

        unset($state['items'][$cargoItemId]);

        if ($state['items'] === []) {
            CargoCart::clear();

            return;
        }

        CargoCart::put($state);
    }
}
