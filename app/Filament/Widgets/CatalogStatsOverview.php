<?php

namespace App\Filament\Widgets;

use App\Enums\CargoStatus;
use App\Enums\CustomRequestStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Cargos\CargoResource;
use App\Filament\Resources\CustomRequests\CustomRequestResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Cargo;
use App\Models\CustomRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\SiteSettings;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CatalogStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Boutique Ôhéfê';

    protected ?string $description = 'Catalogue et opérations gérés directement par l’équipe.';

    protected function getStats(): array
    {
        $lowStockThreshold = SiteSettings::lowStockThreshold();

        $catalogPublished = Product::query()
            ->whereNull('user_id')
            ->where('status', ProductStatus::Published)
            ->count();

        $catalogLowStock = Product::query()
            ->whereNull('user_id')
            ->where('status', ProductStatus::Published)
            ->where('stock_quantity', '<=', $lowStockThreshold)
            ->count();

        $openCargos = Cargo::query()
            ->whereIn('status', [CargoStatus::Open, CargoStatus::InTransit])
            ->count();

        $openCourses = CustomRequest::query()
            ->whereIn('status', [
                CustomRequestStatus::Submitted,
                CustomRequestStatus::InReview,
                CustomRequestStatus::Quoted,
            ])
            ->count();

        $openOrders = Order::query()
            ->whereNotIn('status', [OrderStatus::Delivered, OrderStatus::Cancelled])
            ->count();

        $clients = User::query()->where('role', UserRole::Client)->count();

        return [
            Stat::make('Produits catalogue', $catalogPublished)
                ->description('Publiés par Ôhéfê')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success')
                ->url(ProductResource::getUrl('index')),
            Stat::make('Stock bas catalogue', $catalogLowStock)
                ->description("Seuil ≤ {$lowStockThreshold}")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($catalogLowStock > 0 ? 'danger' : 'gray'),
            Stat::make('Cargos actifs', $openCargos)
                ->description('Ouverts ou en mer')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info')
                ->url(CargoResource::getUrl('index')),
            Stat::make('Courses clients', $openCourses)
                ->description('À traiter (devis / suivi)')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info')
                ->url(CustomRequestResource::getUrl('index')),
            Stat::make('Commandes en cours', $openOrders)
                ->description('Hors livrées / annulées')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color($openOrders > 0 ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('index')),
            Stat::make('Clients', $clients)
                ->description('Comptes boutique')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),
        ];
    }
}
