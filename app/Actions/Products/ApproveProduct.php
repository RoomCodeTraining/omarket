<?php

namespace App\Actions\Products;

use App\Enums\ProductStatus;
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
        }

        $product->forceFill([
            'status' => ProductStatus::Published,
        ])->save();

        return $product->refresh();
    }
}
