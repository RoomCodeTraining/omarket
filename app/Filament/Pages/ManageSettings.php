<?php

namespace App\Filament\Pages;

use App\Support\SiteSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Réglages';

    protected static ?string $title = 'Réglages';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'reglages';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSettings::all());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Boutique Ôhéfê — identité')
                ->description('Paramètres de la boutique gérée directement par l’équipe.')
                ->icon('heroicon-o-building-storefront')
                ->columns(2)
                ->schema([
                    TextInput::make('store_name')
                        ->label('Nom de la boutique')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('store_tagline')
                        ->label('Accroche')
                        ->maxLength(180),
                    TextInput::make('currency_code')
                        ->label('Devise')
                        ->required()
                        ->maxLength(3)
                        ->helperText('Code ISO (ex. CAD).'),
                    TextInput::make('default_product_unit')
                        ->label('Unité produit catalogue')
                        ->required()
                        ->maxLength(40),
                ]),
            Section::make('Boutique Ôhéfê — contact & communication')
                ->description('Coordonnées et message affiché côté storefront.')
                ->icon('heroicon-o-envelope')
                ->columns(2)
                ->schema([
                    TextInput::make('contact_email')
                        ->label('E-mail contact')
                        ->email()
                        ->required()
                        ->maxLength(160),
                    TextInput::make('support_email')
                        ->label('E-mail support')
                        ->email()
                        ->required()
                        ->maxLength(160),
                    TextInput::make('contact_phone')
                        ->label('Téléphone')
                        ->tel()
                        ->maxLength(40),
                    Textarea::make('announcement_banner')
                        ->label('Bannière d’annonce boutique')
                        ->rows(3)
                        ->helperText('Message optionnel pour le storefront (vide = masqué).')
                        ->columnSpanFull(),
                ]),
            Section::make('Boutique Ôhéfê — opérations')
                ->description('Modules et seuils opérés par l’admin (hors validation partenaires).')
                ->icon('heroicon-o-cog-6-tooth')
                ->columns(2)
                ->schema([
                    TextInput::make('low_stock_threshold')
                        ->label('Seuil stock bas (catalogue)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->helperText('KPI stock bas du catalogue Ôhéfê.'),
                    Toggle::make('courses_enabled')
                        ->label('Module courses clients')
                        ->helperText('Courses personnalisées gérées par Ôhéfê.')
                        ->inline(false),
                    Toggle::make('arrivals_enabled')
                        ->label('Module arrivages / cargos')
                        ->helperText('Cargos et réservations gérés par Ôhéfê.')
                        ->inline(false),
                    Toggle::make('checkout_requires_account')
                        ->label('Compte requis pour commander')
                        ->helperText('Si activé, le client doit être connecté ou créer un compte pour valider une commande boutique.')
                        ->inline(false),
                ]),
            Section::make('Entrepôt — dépôt partenaires')
                ->description('Adresse communiquée aux partenaires pour déposer le stock avant l’arrivée du cargo.')
                ->icon('heroicon-o-map-pin')
                ->columns(2)
                ->schema([
                    TextInput::make('warehouse_name')
                        ->label('Nom du site')
                        ->maxLength(160),
                    TextInput::make('warehouse_phone')
                        ->label('Téléphone entrepôt')
                        ->tel()
                        ->maxLength(40),
                    TextInput::make('warehouse_line1')
                        ->label('Adresse ligne 1')
                        ->maxLength(160)
                        ->columnSpanFull(),
                    TextInput::make('warehouse_line2')
                        ->label('Adresse ligne 2')
                        ->maxLength(160)
                        ->columnSpanFull(),
                    TextInput::make('warehouse_city')
                        ->label('Ville')
                        ->maxLength(100),
                    TextInput::make('warehouse_province')
                        ->label('Province')
                        ->maxLength(80),
                    TextInput::make('warehouse_postal_code')
                        ->label('Code postal')
                        ->maxLength(20),
                    TextInput::make('warehouse_country')
                        ->label('Pays')
                        ->maxLength(2)
                        ->helperText('Code ISO (CA).'),
                    TextInput::make('partner_deposit_reminder_days')
                        ->label('Rappel dépôt (jours avant ETA)')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(30)
                        ->helperText('Notification automatique aux partenaires ayant des commandes.'),
                    Textarea::make('warehouse_notes')
                        ->label('Consignes de dépôt')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Section::make('Partenaires')
                ->description('Réglages du canal partenaires — distinct de la boutique catalogue.')
                ->icon('heroicon-o-user-group')
                ->columns(2)
                ->schema([
                    Toggle::make('partner_registration_enabled')
                        ->label('Inscription partenaires ouverte')
                        ->helperText('Active / coupe /partenaires/inscription. N’affecte pas le catalogue Ôhéfê.')
                        ->inline(false),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Enregistrer')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ])->alignment(Alignment::Start),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSettings::putMany([
            'store_name' => $data['store_name'],
            'store_tagline' => $data['store_tagline'] ?? '',
            'contact_email' => $data['contact_email'],
            'support_email' => $data['support_email'],
            'contact_phone' => $data['contact_phone'] ?? '',
            'currency_code' => strtoupper((string) $data['currency_code']),
            'low_stock_threshold' => (int) $data['low_stock_threshold'],
            'partner_registration_enabled' => (bool) ($data['partner_registration_enabled'] ?? false),
            'courses_enabled' => (bool) ($data['courses_enabled'] ?? false),
            'arrivals_enabled' => (bool) ($data['arrivals_enabled'] ?? false),
            'checkout_requires_account' => (bool) ($data['checkout_requires_account'] ?? false),
            'warehouse_name' => $data['warehouse_name'] ?? '',
            'warehouse_line1' => $data['warehouse_line1'] ?? '',
            'warehouse_line2' => $data['warehouse_line2'] ?? '',
            'warehouse_city' => $data['warehouse_city'] ?? '',
            'warehouse_province' => $data['warehouse_province'] ?? '',
            'warehouse_postal_code' => $data['warehouse_postal_code'] ?? '',
            'warehouse_country' => strtoupper((string) ($data['warehouse_country'] ?? 'CA')),
            'warehouse_phone' => $data['warehouse_phone'] ?? '',
            'warehouse_notes' => $data['warehouse_notes'] ?? '',
            'partner_deposit_reminder_days' => (int) ($data['partner_deposit_reminder_days'] ?? 3),
            'announcement_banner' => $data['announcement_banner'] ?? '',
            'default_product_unit' => $data['default_product_unit'],
        ]);

        Notification::make()
            ->title('Réglages enregistrés')
            ->success()
            ->send();
    }
}
