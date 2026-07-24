<?php

namespace App\Http\Controllers;

use App\Enums\CargoStatus;
use App\Enums\ProductStatus;
use App\Models\Cargo;
use App\Models\Product;
use App\Support\CargoCart;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArrivalController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedCargoId = $request->integer('cargo') ?: null;

        $cargos = Cargo::query()
            ->whereIn('status', [CargoStatus::Open, CargoStatus::InTransit])
            ->with(['items.product.category', 'items.product.owner'])
            ->orderBy('estimated_arrival_at')
            ->get();

        $cargoCards = $cargos->map(function (Cargo $cargo) {
            $visibleItems = $cargo->items->filter(
                fn ($item) => $item->product !== null
                    && ($item->product->user_id === null || $item->product->status === ProductStatus::Published),
            );

            return [
                'id' => $cargo->id,
                'code' => $cargo->code,
                'name' => $cargo->name,
                'route' => $cargo->routeLabel(),
                'status' => $cargo->status->value,
                'status_label' => $cargo->status->label(),
                'departure_at' => $cargo->departure_at?->format('d/m/Y'),
                'estimated_arrival_at' => $cargo->estimated_arrival_at?->format('d/m/Y'),
                'products_count' => $visibleItems->count(),
                'accepts_reservations' => in_array($cargo->status, [CargoStatus::Open, CargoStatus::InTransit], true),
            ];
        })->values();

        if ($selectedCargoId !== null && ! $cargos->contains('id', $selectedCargoId)) {
            $selectedCargoId = null;
        }

        $products = $cargos
            ->when(
                $selectedCargoId !== null,
                fn ($collection) => $collection->where('id', $selectedCargoId),
            )
            ->flatMap(function (Cargo $cargo) {
                return $cargo->items
                    ->filter(fn ($item) => $item->product !== null
                        && ($item->product->user_id === null || $item->product->status === ProductStatus::Published))
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'cargo_id' => $cargo->id,
                        'cargo_code' => $cargo->code,
                        'cargo_name' => $cargo->name,
                        'estimated_arrival_at' => $cargo->estimated_arrival_at?->format('d/m/Y'),
                        'product_name' => $item->product?->name,
                        'description' => $item->product?->description,
                        'category' => $item->product?->category?->name,
                        'image_url' => $item->product?->imageUrl() ?? asset(Product::DEFAULT_IMAGE_PATH),
                        'quantity_remaining' => $item->quantityRemaining(),
                        'unit_price' => $item->unitPriceFormatted(),
                        'unit_price_cents' => $item->unit_price_cents,
                        'unit' => $item->product?->unit,
                        'is_partner' => $item->product?->user_id !== null,
                        'partner_name' => $item->product?->owner?->name,
                        'can_reserve' => $item->quantityRemaining() > 0
                            && in_array($cargo->status, [CargoStatus::Open, CargoStatus::InTransit], true),
                    ]);
            })
            ->values();

        return Inertia::render('Arrivals/Index', [
            'cargos' => $cargoCards,
            'products' => $products,
            'filters' => [
                'cargo' => $selectedCargoId,
            ],
            'cargo_cart' => [
                'count' => CargoCart::count(),
                'cargo_id' => CargoCart::cargoId(),
            ],
        ]);
    }
}
