<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\User;
use App\Notifications\PartnerProductDeposited;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class RecordPartnerWarehouseDeposit
{
    public function handle(Product $product, User $admin, int $quantity): Product
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'product' => 'Seul un administrateur Ôhéfê peut enregistrer un dépôt entrepôt.',
            ]);
        }

        if ($product->user_id === null) {
            throw ValidationException::withMessages([
                'product' => 'Ce dépôt ne s’applique qu’aux produits partenaires.',
            ]);
        }

        if ($product->cargo_id === null) {
            throw ValidationException::withMessages([
                'product' => 'Le produit doit être associé à un cargo.',
            ]);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Indiquez une quantité déposée d’au moins 1.',
            ]);
        }

        $product->forceFill([
            'warehouse_deposited_at' => now(),
            'warehouse_deposited_quantity' => $quantity,
        ])->save();

        $product = $product->refresh()->loadMissing('owner');

        $owner = $product->owner;

        if ($owner !== null) {
            Notification::send($owner, new PartnerProductDeposited($product));
        }

        return $product;
    }
}
