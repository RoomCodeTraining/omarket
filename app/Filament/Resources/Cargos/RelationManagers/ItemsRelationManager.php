<?php

namespace App\Filament\Resources\Cargos\RelationManagers;

use App\Actions\Cargos\AddCargoItems;
use App\Filament\Resources\Cargos\Support\CargoItemsRepeater;
use App\Models\CargoItem;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Produits de l’arrivage';

    protected static ?string $modelLabel = 'ligne produit';

    protected static ?string $pluralModelLabel = 'lignes produits';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ligne cargo')
                ->description('Quantités réservables avant arrivée. reserved ≤ available.')
                ->icon('heroicon-o-cube')
                ->columns(2)
                ->schema([
                    Select::make('product_id')
                        ->label('Produit')
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?int $state, Set $set): void {
                            $product = $state ? Product::query()->find($state) : null;

                            if ($product) {
                                $set('unit_price_cents', $product->price_cents);
                            }
                        })
                        ->unique(
                            table: 'cargo_items',
                            column: 'product_id',
                            ignorable: fn (?CargoItem $record): ?CargoItem => $record,
                            modifyRuleUsing: function (Unique $rule): Unique {
                                return $rule->where('cargo_id', $this->getOwnerRecord()->getKey());
                            },
                        ),
                    TextInput::make('unit_price_cents')
                        ->label('Prix unitaire (cents CAD)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('¢')
                        ->helperText('Ex. 2500 = 25,00 $'),
                    TextInput::make('quantity_available')
                        ->label('Quantité disponible')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('quantity_reserved')
                        ->label('Quantité réservée')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->default(0)
                        ->lte('quantity_available')
                        ->helperText('Ne peut pas dépasser la quantité disponible.'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity_available')
                    ->label('Dispo')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('quantity_reserved')
                    ->label('Réservé')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('remaining')
                    ->label('Restant')
                    ->state(fn (CargoItem $record): int => $record->quantityRemaining())
                    ->alignCenter()
                    ->color(fn (CargoItem $record): string => $record->quantityRemaining() > 0 ? 'success' : 'danger'),
                TextColumn::make('unit_price_cents')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('addItems')
                    ->label('Ajouter des produits')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->modalHeading('Ajouter des produits à l’arrivage')
                    ->modalDescription('Saisissez plusieurs lignes : produit, quantité et prix.')
                    ->modalSubmitActionLabel('Enregistrer les lignes')
                    ->form([
                        CargoItemsRepeater::make(
                            excludeProductIds: $this->getOwnerRecord()
                                ->items()
                                ->pluck('product_id')
                                ->all(),
                        ),
                    ])
                    ->action(function (array $data): void {
                        $lines = CargoItemsRepeater::toLines($data['items'] ?? []);
                        $created = app(AddCargoItems::class)->handle($this->getOwnerRecord(), $lines);

                        Notification::make()
                            ->title(count($created).' produit(s) ajouté(s)')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Modifier'),
                DeleteAction::make()->label('Retirer'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Retirer la sélection'),
                ]),
            ])
            ->emptyStateHeading('Aucun produit sur cet arrivage')
            ->emptyStateDescription('Ajoutez plusieurs produits via le bouton « Ajouter des produits ».')
            ->emptyStateActions([
                Action::make('addItemsEmpty')
                    ->label('Ajouter des produits')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Ajouter des produits à l’arrivage')
                    ->modalSubmitActionLabel('Enregistrer les lignes')
                    ->form([
                        CargoItemsRepeater::make(),
                    ])
                    ->action(function (array $data): void {
                        $lines = CargoItemsRepeater::toLines($data['items'] ?? []);
                        $created = app(AddCargoItems::class)->handle($this->getOwnerRecord(), $lines);

                        Notification::make()
                            ->title(count($created).' produit(s) ajouté(s)')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
