<?php

namespace App\Filament\Resources\CustomRequests\Schemas;

use App\Enums\CustomRequestStatus;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CustomRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Client')
                ->description('Coordonnées fournies avec la demande.')
                ->icon('heroicon-o-user')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('guest_name')
                        ->label('Nom')
                        ->maxLength(120),
                    TextInput::make('guest_email')
                        ->label('E-mail')
                        ->email()
                        ->maxLength(160),
                    Select::make('user_id')
                        ->label('Compte client lié')
                        ->relationship('user', 'email')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Optionnel — compte Laravel associé.'),
                ]),
            Section::make('Produits demandés')
                ->description('Chaque ligne : libellé, quantité et budget indicatif.')
                ->icon('heroicon-o-shopping-bag')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->label('Produits')
                        ->schema([
                            TextInput::make('label')
                                ->label('Libellé')
                                ->required()
                                ->maxLength(160)
                                ->columnSpan(2),
                            TextInput::make('quantity')
                                ->label('Quantité')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->default(1),
                            TextInput::make('budget_cents')
                                ->label('Budget (cents CAD)')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('¢')
                                ->helperText('Ex. 4000 = 40,00 $'),
                        ])
                        ->columns(4)
                        ->orderColumn('sort_order')
                        ->defaultItems(1)
                        ->minItems(1)
                        ->addActionLabel('Ajouter un produit')
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Produit')
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->label('Notes')
                        ->rows(4)
                        ->columnSpanFull(),
                    TextInput::make('title')
                        ->label('Titre résumé')
                        ->required()
                        ->maxLength(160)
                        ->helperText('Recalculé automatiquement après enregistrement des lignes.')
                        ->columnSpanFull(),
                    TextInput::make('quantity')
                        ->label('Quantité totale')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->disabled()
                        ->dehydrated(),
                    TextInput::make('budget_cents')
                        ->label('Budget total (cents CAD)')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('¢')
                        ->disabled()
                        ->dehydrated(),
                ]),
            Section::make('Fournisseur')
                ->description('Indiqué par le client à la création.')
                ->icon('heroicon-o-building-storefront')
                ->columns(2)
                ->schema([
                    Toggle::make('has_supplier')
                        ->label('Le client a un fournisseur')
                        ->live()
                        ->inline(false),
                    TextInput::make('supplier_name')
                        ->label('Nom du fournisseur')
                        ->maxLength(160)
                        ->visible(fn (Get $get): bool => (bool) $get('has_supplier')),
                    TextInput::make('supplier_contact')
                        ->label('Contact / détails fournisseur')
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => (bool) $get('has_supplier')),
                ]),
            Section::make('Cargo & suivi')
                ->description('Associez le cargo via l’action dédiée pour notifier le client.')
                ->icon('heroicon-o-truck')
                ->columns(2)
                ->schema([
                    Select::make('cargo_id')
                        ->label('Cargo')
                        ->relationship('cargo', 'code')
                        ->getOptionLabelFromRecordUsing(
                            fn ($record): string => "{$record->code} — {$record->name}",
                        )
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Préférez l’action « Associer un cargo » pour envoyer la notification client.'),
                    Select::make('status')
                        ->label('Statut')
                        ->options(collect(CustomRequestStatus::cases())->mapWithKeys(
                            fn (CustomRequestStatus $status) => [$status->value => $status->label()],
                        ))
                        ->required()
                        ->native(false)
                        ->helperText('Utilisez « Valider la course » puis « Envoyer » sur un devis.'),
                ]),
        ]);
    }
}
