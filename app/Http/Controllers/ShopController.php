<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $categorySlug = $request->string('category')->toString();
        $categorySlug = $categorySlug !== '' ? $categorySlug : null;

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get(['id', 'name', 'slug']);

        $baseQuery = Product::query()->published();

        $products = (clone $baseQuery)
            ->with('category:id,name,slug')
            ->when(
                $categorySlug,
                fn ($query) => $query->whereHas(
                    'category',
                    fn ($categoryQuery) => $categoryQuery->where('slug', $categorySlug),
                ),
            )
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => [
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
                'image_url' => $product->imageUrl(),
                'category' => $product->category?->only(['id', 'name', 'slug']),
            ]);

        return Inertia::render('Shop/Index', [
            'categories' => $categories,
            'products' => $products,
            'filters' => [
                'category' => $categorySlug,
            ],
            'stats' => [
                'products' => (clone $baseQuery)->count(),
                'in_stock' => (clone $baseQuery)->inStock()->count(),
            ],
        ]);
    }
}
