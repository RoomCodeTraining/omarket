<?php

namespace App\Filament\Widgets;

use App\Enums\ProductStatus;
use App\Models\Product;
use Filament\Widgets\ChartWidget;

class PartnerProductsByStatusChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Produits partenaires';

    protected ?string $description = 'Pipeline des fiches soumises par les partenaires';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $counts = Product::query()
            ->whereNotNull('user_id')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $colors = [
            ProductStatus::Draft->value => '#94a3b8',
            ProductStatus::PendingReview->value => '#f59e0b',
            ProductStatus::Published->value => '#16a34a',
            ProductStatus::Archived->value => '#64748b',
        ];

        $labels = [];
        $data = [];
        $background = [];

        foreach (ProductStatus::cases() as $status) {
            $labels[] = $status->label();
            $data[] = (int) ($counts[$status->value] ?? 0);
            $background[] = $colors[$status->value];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Produits partenaires',
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
