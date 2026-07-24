<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Enums\ProductStatus;
use App\Models\CargoItem;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

final class ApproveProduct
{
    public function handle(Product $product): Product
    {
        if ($product->status !== ProductStatus::PendingReview && $product->status !== ProductStatus::Draft) {
            throw ValidationException::withMessages([
                'product' => 'Ce produit ne peut pas être publié dans son état actuel.',
            ]);
        }

        if ($product->user_id !== null) {
            $owner = $product->owner;

            if ($owner && ! $owner->canPublishProducts()) {
                throw ValidationException::withMessages([
                    'product' => 'Le partenaire n’est pas encore autorisé à publier.',
                ]);
            }

            if ($product->cargo_id === null) {
                throw ValidationException::withMessages([
                    'product' => 'Le produit partenaire doit être associé à un cargo.',
                ]);
            }
        }

        $product->forceFill([
            'status' => ProductStatus::Published,
            // Partner / cargo products never appear in the boutique.
            'listed_in_shop' => $product->user_id === null ? $product->listed_in_shop : false,
        ])->save();

        if ($product->user_id !== null && $product->cargo_id !== null) {
            CargoItem::query()->updateOrCreate(
                [
                    'cargo_id' => $product->cargo_id,
                    'product_id' => $product->id,
                ],
                [
                    'quantity_available' => max(0, $product->stock_quantity),
                    'unit_price_cents' => $product->price_cents,
                ],
            );
        }

        return $product->refresh();
    }
}
