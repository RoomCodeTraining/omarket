<?php

namespace App\Filament\Resources\PartnerProducts;

use App\Actions\Products\ApproveProduct;
use App\Actions\Products\RecordPartnerWarehouseDeposit;
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
use Illuminate\Support\Facades\Auth;

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
            ->with(['owner', 'category', 'cargo']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Partenaire')
                ->description('Compte à l’origine de cette fiche — lecture seule.')
                ->icon('heroicon-o-building-storefront')
                ->columnSpanFull()
                ->schema([
                    Placeholder::make('partner_name')
                        ->label('Soumis par')
                        ->content(fn (?Product $record): string => $record?->owner?->name ?? '—'),

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
                    Placeholder::make('cargo_label')
                        ->label('Cargo associé')
                        ->content(fn (?Product $record): string => $record?->cargo
                            ? "{$record->cargo->code} — {$record->cargo->name}"
                            : '—'),
                    Placeholder::make('deposit_status')
                        ->label('Dépôt entrepôt')
                        ->content(function (?Product $record): string {
                            if ($record === null || $record->warehouse_deposited_at === null) {
                                return 'Non déposé — enregistrement réservé à l’admin Ôhéfê';
                            }

                            $qty = $record->warehouse_deposited_quantity;

                            return 'Déposé le '.$record->warehouse_deposited_at->format('d/m/Y H:i')
                                .($qty !== null ? " · quantité {$qty}" : '');
                        }),
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
                ->columnSpanFull()
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
                TextColumn::make('cargo.code')->label('Cargo')->toggleable(),
                TextColumn::make('price_cents')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' $'),
                TextColumn::make('stock_quantity')->label('Stock')->sortable(),
                TextColumn::make('warehouse_deposited_at')
                    ->label('Dépôt')
                    ->formatStateUsing(function ($state, Product $record): string {
                        if (! $state) {
                            return 'En attente';
                        }

                        $qty = $record->warehouse_deposited_quantity;

                        return $qty !== null ? "Déposé ({$qty})" : 'Déposé';
                    })
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'warning'),
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
                TernaryFilter::make('warehouse_deposited_at')
                    ->label('Déposé entrepôt')
                    ->nullable()
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('warehouse_deposited_at'),
                        false: fn (Builder $query) => $query->whereNull('warehouse_deposited_at'),
                        blank: fn (Builder $query) => $query,
                    ),
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
                    ->label('Publier sur arrivage')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Product $record): bool => $record->status === ProductStatus::PendingReview)
                    ->requiresConfirmation()
                    ->modalHeading('Publier le produit partenaire ?')
                    ->modalDescription('Il sera visible avec les produits du cargo (pas en boutique).')
                    ->action(function (Product $record): void {
                        app(ApproveProduct::class)->handle($record);

                        Notification::make()
                            ->title('Produit partenaire publié sur l’arrivage')
                            ->success()
                            ->send();
                    }),
                Action::make('recordDeposit')
                    ->label('Enregistrer dépôt')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->visible(fn (Product $record): bool => $record->cargo_id !== null)
                    ->form([
                        TextInput::make('quantity')
                            ->label('Quantité déposée')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(fn (Product $record): int => max(1, (int) $record->stock_quantity))
                            ->helperText('Quantité reçue à l’entrepôt Ôhéfê — visible par le partenaire.'),
                    ])
                    ->modalHeading('Enregistrer le dépôt entrepôt')
                    ->modalDescription('Le partenaire sera notifié et verra la quantité dans son espace.')
                    ->action(function (Product $record, array $data): void {
                        $admin = Auth::user();

                        if ($admin === null) {
                            return;
                        }

                        app(RecordPartnerWarehouseDeposit::class)->handle(
                            $record,
                            $admin,
                            (int) $data['quantity'],
                        );

                        Notification::make()
                            ->title('Dépôt enregistré')
                            ->body('Le partenaire a été notifié.')
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
