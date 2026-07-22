<?php

namespace App\Http\Controllers;

use App\Actions\Cart\AddProductToCart;
use App\Actions\Cart\RemoveCartItem;
use App\Actions\Cart\UpdateCartItem;
use App\Actions\Orders\PlaceCartOrder;
use App\Http\Requests\Cart\CheckoutCartRequest;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(): Response
    {
        $items = Cart::detailed();

        return Inertia::render('Cart/Index', [
            'items' => $items,
            'total' => number_format(Cart::totalCents() / 100, 2, ',', ' ').' $',
            'total_cents' => Cart::totalCents(),
            'count' => Cart::count(),
            'can_checkout' => $items->isNotEmpty() && $items->every(fn (array $item) => $item['in_stock']),
        ]);
    }

    public function store(StoreCartItemRequest $request, AddProductToCart $action): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $action->handle($product, $request->integer('quantity', 1));

        return back()->with('success', "« {$product->name} » ajouté au panier.");
    }

    public function update(UpdateCartItemRequest $request, int $productId, UpdateCartItem $action): RedirectResponse
    {
        $action->handle($productId, $request->integer('quantity'));

        return back()->with('success', 'Panier mis à jour.');
    }

    public function destroy(int $productId, RemoveCartItem $action): RedirectResponse
    {
        $action->handle($productId);

        return back()->with('success', 'Article retiré du panier.');
    }

    public function checkout(CheckoutCartRequest $request, PlaceCartOrder $action): RedirectResponse
    {
        $order = $action->handle($request->payload(), $request->user());

        $request->session()->put('order_access.'.$order->id, $order->guest_email);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', "Commande {$order->reference} enregistrée.");
    }
}
