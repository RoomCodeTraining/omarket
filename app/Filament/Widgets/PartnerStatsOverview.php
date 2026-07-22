<?php

namespace App\Filament\Widgets;

use App\Enums\ProductStatus;
use App\Filament\Resources\PartnerProducts\PartnerProductResource;
use App\Filament\Resources\Partners\PartnerResource;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PartnerStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Partenaires';

    protected ?string $description = 'Comptes et fiches soumises par les partenaires — validation admin requise.';

    protected function getStats(): array
    {
        $pendingPartners = User::query()
            ->partners()
            ->where('can_publish', false)
            ->count();

        $approvedPartners = User::query()
            ->partners()
            ->where('can_publish', true)
            ->count();

        $pendingProducts = Product::query()
            ->whereNotNull('user_id')
            ->where('status', ProductStatus::PendingReview)
            ->count();

        $partnerPublished = Product::query()
            ->whereNotNull('user_id')
            ->where('status', ProductStatus::Published)
            ->count();

        return [
            Stat::make('Comptes à autoriser', $pendingPartners)
                ->description('Sans droit de publication')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color($pendingPartners > 0 ? 'warning' : 'success')
                ->url(PartnerResource::getUrl('index')),
            Stat::make('Partenaires actifs', $approvedPartners)
                ->description('Autorisés à soumettre')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->url(PartnerResource::getUrl('index')),
            Stat::make('Produits à valider', $pendingProducts)
                ->description('Revue partenaire')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($pendingProducts > 0 ? 'warning' : 'success')
                ->url(PartnerProductResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['value' => ProductStatus::PendingReview->value],
                    ],
                ])),
            Stat::make('Produits partenaires live', $partnerPublished)
                ->description('Déjà publiés en boutique')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info')
                ->url(PartnerProductResource::getUrl('index')),
        ];
    }
}
