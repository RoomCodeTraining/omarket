<?php

namespace App\Filament\Widgets;

use App\Actions\Products\ApproveProduct;
use App\Enums\ProductStatus;
use App\Filament\Resources\PartnerProducts\PartnerProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingProductReviews extends TableWidget
{
    protected static ?int $sort = 6;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('File partenaires — produits à valider')
            ->description('Fiches soumises par les partenaires. Distinct du catalogue Ôhéfê.')
            ->query(
                fn (): Builder => Product::query()
                    ->with(['owner:id,name', 'category:id,name'])
                    ->whereNotNull('user_id')
                    ->where('status', ProductStatus::PendingReview)
                    ->latest(),
            )
            ->columns([
                TextColumn::make('name')->label('Produit')->searchable()->limit(40),
                TextColumn::make('owner.name')->label('Partenaire'),
                TextColumn::make('category.name')->label('Catégorie'),
                TextColumn::make('price_cents')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $'),
                TextColumn::make('updated_at')->label('Soumis')->since(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Examiner')
                    ->url(fn (Product $record): string => PartnerProductResource::getUrl('edit', ['record' => $record])),
                Action::make('publish')
                    ->label('Publier')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Product $record, ApproveProduct $approveProduct): void {
                        $approveProduct->handle($record);

                        Notification::make()
                            ->title("Produit partenaire « {$record->name} » publié")
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Aucune revue partenaire')
            ->emptyStateDescription('Les soumissions partenaires apparaîtront ici.');
    }
}
