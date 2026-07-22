<?php

namespace App\Filament\Resources\PartnerProducts;

use App\Actions\Products\ApproveProduct;
use App\Enums\ProductStatus;
use App\Filament\Resources\PartnerProducts\Pages\EditPartnerProduct;
use App\Filament\Resources\PartnerProducts\Pages\ListPartnerProducts;
use App\Models\Product;
use App\Support\ProductImageUpload;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PartnerProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = 'Produits partenaires';

    protected static ?string $modelLabel = 'produit partenaire';

    protected static ?string $pluralModelLabel = 'produits partenaires';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Partenaires';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('user_id')
            ->with(['owner', 'category']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Partenaire')
                ->description('Compte à l’origine de cette fiche — lecture seule.')
                ->icon('heroicon-o-building-storefront')
                ->schema([
                    Placeholder::make('partner_name')
                        ->label('Soumis par')
                        ->content(fn (?Product $record): string => $record?->owner?->name ?? '—'),
                ]),
            Section::make('Identité du produit')
                ->description('Contenu proposé par le partenaire.')
                ->icon('heroicon-o-tag')
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
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(180)
                        ->unique(ignoreRecord: true)
                        ->helperText('URL boutique.'),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),
            Section::make('Prix & stock')
                ->description('Tarification et quantités déclarées.')
                ->icon('heroicon-o-banknotes')
                ->columns(3)
                ->schema([
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
                        ->maxLength(40),
                ]),
            Section::make('Validation')
                ->description('Après revue, publiez via l’action « Publier » plutôt qu’un changement manuel de statut.')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Select::make('status')
                        ->label('Statut')
                        ->options(collect(ProductStatus::cases())->mapWithKeys(
                            fn (ProductStatus $status) => [$status->value => $status->label()],
                        ))
                        ->required(),
                ]),
            Section::make('Visuel')
                ->description('Image produit affichée en boutique après publication.')
                ->icon('heroicon-o-photo')
                ->schema([
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
                TextColumn::make('name')->label('Produit')->searchable()->sortable(),
                TextColumn::make('owner.name')->label('Partenaire')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Catégorie')->toggleable(),
                TextColumn::make('price_cents')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $'),
                TextColumn::make('stock_quantity')->label('Stock')->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (ProductStatus $state): string => match ($state) {
                        ProductStatus::PendingReview => 'warning',
                        ProductStatus::Published => 'success',
                        ProductStatus::Draft => 'gray',
                        ProductStatus::Archived => 'danger',
                    })
                    ->formatStateUsing(fn (ProductStatus $state): string => $state->label()),
                TextColumn::make('updated_at')->label('Mis à jour')->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(ProductStatus::cases())->mapWithKeys(
                        fn (ProductStatus $status) => [$status->value => $status->label()],
                    )),
                TernaryFilter::make('awaiting_review')
                    ->label('En revue uniquement')
                    ->queries(
                        true: fn (Builder $query) => $query->where('status', ProductStatus::PendingReview),
                        false: fn (Builder $query) => $query->where('status', '!=', ProductStatus::PendingReview),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                EditAction::make()->label('Examiner'),
                Action::make('approve')
                    ->label('Publier')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Product $record): bool => $record->status === ProductStatus::PendingReview)
                    ->requiresConfirmation()
                    ->modalHeading('Publier le produit partenaire ?')
                    ->modalDescription('Il sera visible en boutique après publication.')
                    ->action(function (Product $record, ApproveProduct $approveProduct): void {
                        $approveProduct->handle($record);

                        Notification::make()
                            ->title('Produit partenaire publié')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartnerProducts::route('/'),
            'edit' => EditPartnerProduct::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
