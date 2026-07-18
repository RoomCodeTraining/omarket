<?php

namespace App\Actions\Cart;

use App\Support\Cart;

final class RemoveCartItem
{
    public function handle(int $productId): void
    {
        $items = Cart::raw();
        unset($items[$productId]);
        Cart::put($items);
    }
}
