<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use Inertia\Inertia;
use Inertia\Response;

class ArrivalController extends Controller
{
    public function index(): Response
    {
        $cargos = Cargo::query()
            ->openForReservation()
            ->with(['items.product.category'])
            ->orderBy('estimated_arrival_at')
            ->get()
            ->map(fn (Cargo $cargo) => [
                'id' => $cargo->id,
                'code' => $cargo->code,
                'name' => $cargo->name,
                'route' => $cargo->routeLabel(),
                'status' => $cargo->status->value,
                'status_label' => $cargo->status->label(),
                'departure_at' => $cargo->departure_at?->format('d/m/Y'),
                'estimated_arrival_at' => $cargo->estimated_arrival_at?->format('d/m/Y'),
                'notes' => $cargo->notes,
                'items' => $cargo->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product?->name,
                    'category' => $item->product?->category?->name,
                    'quantity_remaining' => $item->quantityRemaining(),
                    'quantity_available' => $item->quantity_available,
                    'quantity_reserved' => $item->quantity_reserved,
                    'unit_price' => $item->unitPriceFormatted(),
                    'unit' => $item->product?->unit,
                ]),
            ]);

        $next = $cargos->first();

        return Inertia::render('Arrivals/Index', [
            'cargos' => $cargos,
            'nextArrival' => $next ? [
                'label' => $next['route'],
                'eta' => $next['estimated_arrival_at'] ?? 'Bientôt',
                'name' => $next['name'],
                'code' => $next['code'],
            ] : null,
        ]);
    }
}
