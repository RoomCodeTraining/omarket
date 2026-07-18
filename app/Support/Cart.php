<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

final class Cart
{
    public const SESSION_KEY = 'cart.items';

    /**
     * @return array<int, array{product_id: int, quantity: int}>
     */
    public static function raw(): array
    {
        /** @var array<int, array{product_id: int, quantity: int}> $items */
        $items = Session::get(self::SESSION_KEY, []);

        return $items;
    }

    public static function put(array $items): void
    {
        Session::put(self::SESSION_KEY, $items);
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public static function count(): int
    {
        return collect(self::raw())->sum('quantity');
    }

    /**
     * @return Collection<int, array{product_id: int, quantity: int, name: string, unit: string, price_cents: int, price: string, stock_quantity: int, in_stock: bool, image_url: string, line_total_cents: int, line_total: string, category: string|null}>
     */
    public static function detailed(): Collection
    {
        $raw = self::raw();

        if ($raw === []) {
            return collect();
        }

        $products = Product::query()
            ->with('category:id,name')
            ->whereIn('id', collect($raw)->pluck('product_id'))
            ->get()
            ->keyBy('id');

        return collect($raw)->map(function (array $item) use ($products) {
            $product = $products->get($item['product_id']);

            return [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'name' => $product?->name ?? 'Produit indisponible',
                'unit' => $product?->unit ?? 'unité',
                'price_cents' => $product?->price_cents ?? 0,
                'price' => $product?->priceFormatted() ?? '0,00 $',
                'stock_quantity' => $product?->stock_quantity ?? 0,
                'in_stock' => $product?->isAvailableLocally() ?? false,
                'image_url' => $product?->imageUrl() ?? asset(Product::DEFAULT_IMAGE_PATH),
                'line_total_cents' => ($product?->price_cents ?? 0) * $item['quantity'],
                'line_total' => number_format((($product?->price_cents ?? 0) * $item['quantity']) / 100, 2, ',', ' ').' $',
                'category' => $product?->category?->name,
            ];
        })->values();
    }

    public static function totalCents(): int
    {
        return (int) self::detailed()->sum('line_total_cents');
    }
}
