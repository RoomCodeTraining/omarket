<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Enums\CargoStatus;
use App\Models\CargoItem;
use App\Support\CargoCart;
use Illuminate\Validation\ValidationException;

final class AddCargoItemToCart
{
    public function handle(CargoItem $cargoItem, int $quantity = 1): void
    {
        $cargoItem->loadMissing('cargo', 'product');

        if ($cargoItem->cargo === null) {
            throw ValidationException::withMessages([
                'cargo_item_id' => 'Ligne d’arrivage introuvable.',
            ]);
        }

        if (! in_array($cargoItem->cargo->status, [CargoStatus::Open, CargoStatus::InTransit], true)) {
            throw ValidationException::withMessages([
                'cargo_item_id' => 'Ce cargo n’accepte plus de réservations.',
            ]);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'La quantité doit être au moins 1.',
            ]);
        }

        if ($cargoItem->quantityRemaining() < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Quantité insuffisante (reste : {$cargoItem->quantityRemaining()}).",
            ]);
        }

        $state = CargoCart::state();

        if ($state['cargo_id'] !== null && $state['cargo_id'] !== $cargoItem->cargo_id && $state['items'] !== []) {
            throw ValidationException::withMessages([
                'cargo_item_id' => 'Votre panier arrivage contient déjà des produits d’un autre cargo. Videz-le ou validez-le avant d’ajouter ceux-ci.',
            ]);
        }

        $items = $state['items'];
        $currentQty = $items[$cargoItem->id]['quantity'] ?? 0;
        $newQty = $currentQty + $quantity;

        if ($newQty > $cargoItem->quantityRemaining()) {
            throw ValidationException::withMessages([
                'quantity' => "Quantité insuffisante (reste : {$cargoItem->quantityRemaining()}).",
            ]);
        }

        $items[$cargoItem->id] = [
            'cargo_item_id' => $cargoItem->id,
            'quantity' => $newQty,
        ];

        CargoCart::put([
            'cargo_id' => $cargoItem->cargo_id,
            'items' => $items,
        ]);
    }
}
