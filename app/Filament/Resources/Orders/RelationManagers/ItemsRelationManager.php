<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Lignes de commande';

    protected static ?string $modelLabel = 'ligne';

    protected static ?string $pluralModelLabel = 'lignes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ligne')
                ->icon('heroicon-o-shopping-bag')
                ->columns(2)
                ->schema([
                    Select::make('product_id')
                        ->label('Produit catalogue')
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->live()
                        ->afterStateUpdated(function (?int $state, callable $set): void {
                            if (! $state) {
                                return;
                            }

                            $product = Product::query()->find($state);

                            if (! $product) {
                                return;
                            }

                            $set('product_name', $product->name);
                            $set('product_unit', $product->unit);
                            $set('unit_price_cents', $product->price_cents);
                        }),
                    TextInput::make('product_name')
                        ->label('Nom (snapshot)')
                        ->required()
                        ->maxLength(160),
                    TextInput::make('product_unit')
                        ->label('Unité')
                        ->required()
                        ->maxLength(40)
                        ->default('unité'),
                    TextInput::make('unit_price_cents')
                        ->label('Prix unitaire (cents)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('¢'),
                    TextInput::make('quantity')
                        ->label('Quantité')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(1),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('product_name')
                    ->label('Produit')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('product_unit')->label('Unité'),
                TextColumn::make('quantity')->label('Qté')->alignCenter(),
                TextColumn::make('unit_price_cents')
                    ->label('Prix unit.')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $'),
                TextColumn::make('line_total_cents')
                    ->label('Total ligne')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $')
                    ->weight('medium'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter une ligne')
                    ->modalHeading('Ajouter une ligne de commande'),
            ])
            ->recordActions([
                EditAction::make()->label('Modifier'),
                DeleteAction::make()->label('Retirer'),
            ])
            ->emptyStateHeading('Aucune ligne')
            ->emptyStateDescription('Ajoutez les produits de cette commande.');
    }
}
