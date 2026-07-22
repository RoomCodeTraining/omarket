<?php

namespace App\Filament\Widgets;

use App\Enums\ProductStatus;
use App\Models\Product;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class ProductsByStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Catalogue Ôhéfê';

    protected ?string $description = 'Répartition des produits gérés par l’équipe';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        return $this->chartData(
            Product::query()->whereNull('user_id'),
            [
                ProductStatus::Draft->value => '#94a3b8',
                ProductStatus::Published->value => '#16a34a',
                ProductStatus::Archived->value => '#64748b',
            ],
            [ProductStatus::Draft, ProductStatus::Published, ProductStatus::Archived],
        );
    }

    /**
     * @param  array<string, string>  $colors
     * @param  list<ProductStatus>  $statuses
     * @return array{datasets: list<array{label: string, data: list<int>, backgroundColor: list<string>}>, labels: list<string>}
     */
    protected function chartData(Builder $query, array $colors, array $statuses): array
    {
        $counts = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $labels = [];
        $data = [];
        $background = [];

        foreach ($statuses as $status) {
            $labels[] = $status->label();
            $data[] = (int) ($counts[$status->value] ?? 0);
            $background[] = $colors[$status->value];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Produits',
                    'data' => $data,
                    'backgroundColor' => $background,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
