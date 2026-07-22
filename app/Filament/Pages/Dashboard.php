<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CatalogStatsOverview;
use App\Filament\Widgets\CoursesByStatusChart;
use App\Filament\Widgets\PartnerProductsByStatusChart;
use App\Filament\Widgets\PartnerStatsOverview;
use App\Filament\Widgets\PendingPartnerApprovals;
use App\Filament\Widgets\PendingProductReviews;
use App\Filament\Widgets\ProductsByStatusChart;
use App\Support\SiteSettings;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string|Htmlable
    {
        return 'Tableau de bord';
    }

    public function getHeading(): string|Htmlable
    {
        return SiteSettings::storeName();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Boutique Ôhéfê (gestion directe) · Partenaires (validation requise)';
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            CatalogStatsOverview::class,
            PartnerStatsOverview::class,
            ProductsByStatusChart::class,
            PartnerProductsByStatusChart::class,
            CoursesByStatusChart::class,
            PendingProductReviews::class,
            PendingPartnerApprovals::class,
        ];
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 2,
        ];
    }
}
