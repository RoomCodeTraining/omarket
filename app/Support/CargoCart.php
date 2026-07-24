<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Cargo;
use App\Models\CargoItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

final class CargoCart
{
    public const SESSION_KEY = 'cargo_cart';

    /**
     * @return array{cargo_id: int|null, items: array<int, array{cargo_item_id: int, quantity: int}>}
     */
    public static function state(): array
    {
        /** @var array{cargo_id?: int|null, items?: array<int, array{cargo_item_id: int, quantity: int}>} $state */
        $state = Session::get(self::SESSION_KEY, [
            'cargo_id' => null,
            'items' => [],
        ]);

        return [
            'cargo_id' => isset($state['cargo_id']) ? (int) $state['cargo_id'] : null,
            'items' => $state['items'] ?? [],
        ];
    }

    /**
     * @param  array{cargo_id: int|null, items: array<int, array{cargo_item_id: int, quantity: int}>}  $state
     */
    public static function put(array $state): void
    {
        Session::put(self::SESSION_KEY, $state);
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public static function cargoId(): ?int
    {
        return self::state()['cargo_id'];
    }

    /**
     * @return array<int, array{cargo_item_id: int, quantity: int}>
     */
    public static function raw(): array
    {
        return self::state()['items'];
    }

    public static function count(): int
    {
        return (int) collect(self::raw())->sum('quantity');
    }

    /**
     * @return Collection<int, array{
     *     cargo_item_id: int,
     *     quantity: int,
     *     name: string,
     *     unit: string,
     *     price_cents: int,
     *     price: string,
     *     quantity_remaining: int,
     *     available: bool,
     *     image_url: string,
     *     line_total_cents: int,
     *     line_total: string,
     *     category: string|null
     * }>
     */
    public static function detailed(): Collection
    {
        $raw = self::raw();

        if ($raw === []) {
            return collect();
        }

        $items = CargoItem::query()
            ->with(['product.category', 'cargo'])
            ->whereIn('id', collect($raw)->pluck('cargo_item_id'))
            ->get()
            ->keyBy('id');

        return collect($raw)->map(function (array $line) use ($items) {
            /** @var CargoItem|null $item */
            $item = $items->get($line['cargo_item_id']);
            $product = $item?->product;
            $qty = (int) $line['quantity'];
            $price = $item?->unit_price_cents ?? 0;
            $remaining = $item?->quantityRemaining() ?? 0;

            return [
                'cargo_item_id' => (int) $line['cargo_item_id'],
                'quantity' => $qty,
                'name' => $product?->name ?? 'Produit indisponible',
                'unit' => $product?->unit ?? 'unité',
                'price_cents' => $price,
                'price' => $item?->unitPriceFormatted() ?? '0,00 $',
                'quantity_remaining' => $remaining,
                'available' => $item !== null && $remaining >= $qty,
                'image_url' => $product?->imageUrl() ?? asset(Product::DEFAULT_IMAGE_PATH),
                'line_total_cents' => $price * $qty,
                'line_total' => number_format(($price * $qty) / 100, 2, ',', ' ').' $',
                'category' => $product?->category?->name,
            ];
        })->values();
    }

    public static function totalCents(): int
    {
        return (int) self::detailed()->sum('line_total_cents');
    }

    public static function cargo(): ?Cargo
    {
        $id = self::cargoId();

        if ($id === null) {
            return null;
        }

        return Cargo::query()->find($id);
    }
}
