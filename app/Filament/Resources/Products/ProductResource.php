<?php

namespace App\Filament\Resources\Products;

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Support\ProductImageUpload;
use App\Support\SiteSettings;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = 'Catalogue Ôhéfê';

    protected static ?string $modelLabel = 'produit catalogue';

    protected static ?string $pluralModelLabel = 'produits catalogue';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Boutique Ôhéfê';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('user_id');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identité du produit')
                ->description('Informations visibles en boutique.')
                ->icon('heroicon-o-tag')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('category_id')
                        ->label('Catégorie')
                        ->relationship('category', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(160),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(5)
                        ->columnSpanFull(),

                    TextInput::make('price_cents')
                        ->label('Prix (cents CAD)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('¢')
                        ->helperText('Ex. 1299 = 12,99 $'),
                    TextInput::make('stock_quantity')
                        ->label('Stock')
                        ->numeric()
                        ->required()
                        ->minValue(0),
                    TextInput::make('unit')
                        ->label('Unité')
                        ->required()
                        ->maxLength(40)
                        ->default(fn (): string => SiteSettings::defaultProductUnit()),
                ]),
            Section::make('Publication & visuel')
                ->description('Activez le produit pour l’afficher dans la boutique et définissez le visuel.')
                ->icon('heroicon-o-eye')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Statut')
                        ->options(collect(ProductStatus::cases())
                            ->reject(fn (ProductStatus $status) => $status === ProductStatus::PendingReview)
                            ->mapWithKeys(fn (ProductStatus $status) => [$status->value => $status->label()]))
                        ->required()
                        ->default(ProductStatus::Published->value),
                    Toggle::make('listed_in_shop')
                        ->label('Visible en boutique')
                        ->helperText('Désactivé = produit hors boutique (ex. réservé cargo / stock interne).')
                        ->default(true)
                        ->inline(false),
                    Toggle::make('is_featured')
                        ->label('Coup de cœur')
                        ->helperText('Mis en avant sur la page d’accueil (si visible en boutique).')
                        ->inline(false),

                    ProductImageUpload::make(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->height(40)
                    ->checkFileExistence(false)
                    ->getStateUsing(fn (Product $record): string => $record->imageUrl()),
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Catégorie')->toggleable(),
                TextColumn::make('price_cents')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '  ,', ' ').' $'),
                TextColumn::make('stock_quantity')->label('Stock')->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ProductStatus $state): string => $state->label()),
                IconColumn::make('is_featured')->label('Coup de cœur')->boolean()->toggleable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(ProductStatus::cases())
                        ->reject(fn (ProductStatus $status) => $status === ProductStatus::PendingReview)
                        ->mapWithKeys(fn (ProductStatus $status) => [$status->value => $status->label()])),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
