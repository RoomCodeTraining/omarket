<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cargos\Support;

use App\Models\Product;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;

final class CargoItemsRepeater
{
    /**
     * @param  list<int>  $excludeProductIds
     */
    public static function make(array $excludeProductIds = [], string $name = 'items'): Repeater
    {
        return Repeater::make($name)
            ->label('Produits')
            ->schema([
                Select::make('product_id')
                    ->label('Produit')
                    ->options(fn (): array => Product::query()
                        ->when(
                            $excludeProductIds !== [],
                            fn (Builder $query) => $query->whereNotIn('id', $excludeProductIds),
                        )
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set): void {
                        if ($state === null) {
                            return;
                        }

                        $product = Product::query()->find($state);

                        if ($product === null) {
                            return;
                        }

                        $set('unit_price', round($product->price_cents / 100, 2));
                    })
                    ->columnSpan(2),
                TextInput::make('quantity_available')
                    ->label('Quantité')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(1),
                TextInput::make('unit_price')
                    ->label('Prix unitaire (CAD)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('$')
                    ->default(fn (Get $get): ?float => null),
            ])
            ->columns(4)
            ->defaultItems(1)
            ->minItems(1)
            ->addActionLabel('Ajouter une ligne')
            ->reorderable(false)
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => filled($state['product_id'] ?? null)
                ? (Product::query()->find($state['product_id'])?->name ?? 'Produit')
                : 'Nouvelle ligne')
            ->columnSpanFull();
    }

    /**
     * @param  list<array{product_id?: mixed, quantity_available?: mixed, unit_price?: mixed}>  $items
     * @return list<array{product_id: int, quantity_available: int, unit_price_cents: int}>
     */
    public static function toLines(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            if (! isset($item['product_id'])) {
                continue;
            }

            $lines[] = [
                'product_id' => (int) $item['product_id'],
                'quantity_available' => max(1, (int) ($item['quantity_available'] ?? 1)),
                'unit_price_cents' => (int) round(((float) ($item['unit_price'] ?? 0)) * 100),
            ];
        }

        return $lines;
    }
}
