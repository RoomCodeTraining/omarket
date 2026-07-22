<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class UnpublishPartnerProduct
{
    public function handle(Product $product, User $partner): Product
    {
        if (! $product->isOwnedBy($partner)) {
            throw ValidationException::withMessages([
                'product' => 'Vous ne pouvez dépublier que vos propres produits.',
            ]);
        }

        if ($product->status !== ProductStatus::Published) {
            throw ValidationException::withMessages([
                'product' => 'Seuls les produits publiés peuvent être dépubliés.',
            ]);
        }

        $product->forceFill([
            'status' => ProductStatus::Archived,
        ])->save();

        return $product->refresh();
    }
}
