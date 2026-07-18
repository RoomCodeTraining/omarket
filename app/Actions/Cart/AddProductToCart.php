<?php

namespace App\Actions\Cart;

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Validation\ValidationException;

final class AddProductToCart
{
    public function handle(Product $product, int $quantity = 1): void
    {
        if (! $product->isAvailableLocally()) {
            throw ValidationException::withMessages([
                'product' => 'Ce produit n’est plus disponible en stock.',
            ]);
        }

        $items = Cart::raw();
        $currentQty = $items[$product->id]['quantity'] ?? 0;
        $newQty = $currentQty + $quantity;

        if ($newQty > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Stock insuffisant (disponible : {$product->stock_quantity}).",
            ]);
        }

        $items[$product->id] = [
            'product_id' => $product->id,
            'quantity' => $newQty,
        ];

        Cart::put($items);
    }
}
