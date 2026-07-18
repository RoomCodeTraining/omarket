<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get(['id', 'name', 'slug']);

        $products = Product::query()
            ->with('category:id,name,slug')
            ->published()
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price_cents' => $product->price_cents,
                'price' => $product->priceFormatted(),
                'stock_quantity' => $product->stock_quantity,
                'unit' => $product->unit,
                'is_featured' => $product->is_featured,
                'in_stock' => $product->isAvailableLocally(),
                'category' => $product->category?->only(['id', 'name', 'slug']),
            ]);

        return Inertia::render('Shop/Index', [
            'categories' => $categories,
            'products' => $products,
            'stats' => [
                'products' => $products->count(),
                'in_stock' => $products->where('in_stock', true)->count(),
            ],
        ]);
    }
}
