<?php

namespace App\Actions\Cart;

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Validation\ValidationException;

final class UpdateCartItem
{
    public function handle(int $productId, int $quantity): void
    {
        $items = Cart::raw();

        if (! isset($items[$productId])) {
            throw ValidationException::withMessages([
                'product' => 'Cet article n’est pas dans le panier.',
            ]);
        }

        if ($quantity <= 0) {
            unset($items[$productId]);
            Cart::put($items);

            return;
        }

        $product = Product::query()->findOrFail($productId);

        if ($quantity > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Stock insuffisant (disponible : {$product->stock_quantity}).",
            ]);
        }

        $items[$productId]['quantity'] = $quantity;
        Cart::put($items);
    }
}
