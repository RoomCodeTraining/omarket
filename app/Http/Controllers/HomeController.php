<?php

namespace App\Http\Controllers;

use App\Enums\CargoStatus;
use App\Models\Cargo;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $nextCargo = Cargo::query()
            ->withCount('items')
            ->where('status', CargoStatus::Open)
            ->orderBy('estimated_arrival_at')
            ->first()
            ?? Cargo::query()
                ->withCount('items')
                ->whereIn('status', [CargoStatus::Open, CargoStatus::Draft])
                ->orderBy('estimated_arrival_at')
                ->first();

        $featured = Product::query()
            ->with('category:id,name')
            ->published()
            ->where('is_featured', true)
            ->orderBy('name')
            ->limit(4)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->priceFormatted(),
                'unit' => $product->unit,
                'in_stock' => $product->isAvailableLocally(),
                'category' => $product->category?->name,
                'image_url' => $product->imageUrl(),
            ]);

        return Inertia::render('Home', [
            'nextArrival' => $nextCargo ? [
                'label' => $nextCargo->routeLabel(),
                'name' => $nextCargo->name,
                'eta' => $nextCargo->estimated_arrival_at?->format('d/m/Y') ?? 'Annonce à venir',
                'items_count' => $nextCargo->items_count,
                'status' => $nextCargo->status->label(),
            ] : null,
            'featuredProducts' => $featured,
        ]);
    }
}
