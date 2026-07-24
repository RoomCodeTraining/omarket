<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Cart\AddCargoItemToCart;
use App\Actions\Cart\RemoveCargoCartItem;
use App\Actions\Cart\UpdateCargoCartItem;
use App\Actions\Orders\PlaceCargoCartOrder;
use App\Http\Requests\Cart\CheckoutCargoCartRequest;
use App\Http\Requests\Cart\StoreCargoCartItemRequest;
use App\Http\Requests\Cart\UpdateCargoCartItemRequest;
use App\Models\CargoItem;
use App\Support\CargoCart;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CargoCartController extends Controller
{
    public function index(): Response
    {
        $cargo = CargoCart::cargo();
        $items = CargoCart::detailed();

        return Inertia::render('Arrivals/Cart', [
            'items' => $items,
            'total' => number_format(CargoCart::totalCents() / 100, 2, ',', ' ').' $',
            'total_cents' => CargoCart::totalCents(),
            'count' => CargoCart::count(),
            'can_checkout' => $items->isNotEmpty() && $items->every(fn (array $item) => $item['available']),
            'checkout_requires_account' => SiteSettings::checkoutRequiresAccount(),
            'cargo' => $cargo ? [
                'id' => $cargo->id,
                'code' => $cargo->code,
                'name' => $cargo->name,
                'route' => $cargo->routeLabel(),
                'estimated_arrival_at' => $cargo->estimated_arrival_at?->format('d/m/Y'),
                'status_label' => $cargo->status->label(),
            ] : null,
        ]);
    }

    public function store(StoreCargoCartItemRequest $request, AddCargoItemToCart $action): RedirectResponse
    {
        $item = CargoItem::query()->with('cargo')->findOrFail($request->integer('cargo_item_id'));
        $action->handle($item, $request->integer('quantity', 1));

        return back()->with(
            'success',
            "« {$item->product?->name} » ajouté au panier arrivage ({$item->cargo?->code}).",
        );
    }

    public function update(
        UpdateCargoCartItemRequest $request,
        int $cargoItemId,
        UpdateCargoCartItem $action,
    ): RedirectResponse {
        $action->handle($cargoItemId, $request->integer('quantity'));

        return back()->with('success', 'Panier arrivage mis à jour.');
    }

    public function destroy(int $cargoItemId, RemoveCargoCartItem $action): RedirectResponse
    {
        $action->handle($cargoItemId);

        return back()->with('success', 'Article retiré du panier arrivage.');
    }

    public function checkout(CheckoutCargoCartRequest $request, PlaceCargoCartOrder $action): RedirectResponse
    {
        $order = $action->handle($request->payload(), $request->user());

        $request->session()->put('order_access.'.$order->id, $order->guest_email);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', "Réservation {$order->reference} enregistrée pour le cargo {$order->cargo?->code}.");
    }

    public function clear(): RedirectResponse
    {
        CargoCart::clear();

        return back()->with('success', 'Panier arrivage vidé.');
    }
}
