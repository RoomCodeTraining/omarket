<?php

namespace App\Filament\Widgets;

use App\Enums\CustomRequestStatus;
use App\Models\CustomRequest;
use Filament\Widgets\ChartWidget;

class CoursesByStatusChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Courses clients (Ôhéfê)';

    protected ?string $description = 'Demandes personnalisées traitées par l’équipe — hors partenaires';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $counts = CustomRequest::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $labels = [];
        $data = [];
        $colors = [
            CustomRequestStatus::Submitted->value => '#f59e0b',
            CustomRequestStatus::InReview->value => '#3b82f6',
            CustomRequestStatus::Quoted->value => '#8b5cf6',
            CustomRequestStatus::Accepted->value => '#16a34a',
            CustomRequestStatus::Rejected->value => '#ef4444',
            CustomRequestStatus::Cancelled->value => '#94a3b8',
        ];
        $background = [];

        foreach (CustomRequestStatus::cases() as $status) {
            $labels[] = $status->label();
            $data[] = (int) ($counts[$status->value] ?? 0);
            $background[] = $colors[$status->value];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Courses',
                    'data' => $data,
                    'backgroundColor' => $background,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
