<?php

namespace App\Filament\Pages;

use App\Support\SiteSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
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
        $settings = SiteSettings::all();

        if (($settings['brand_logo'] ?? '') === '') {
            $settings['brand_logo'] = null;
        }

        $this->form->fill($settings);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Apparence')
                ->description('Logo et couleur primaire — appliqués à la boutique et à l’admin sans déploiement.')
                ->icon('heroicon-o-swatch')
                ->columns(2)
                ->schema([
                    FileUpload::make('brand_logo')
                        ->label('Logo')
                        ->image()
                        ->disk('public')
                        ->directory('branding')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->acceptedFileTypes([
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/svg+xml',
                        ])
                        ->helperText('PNG, SVG ou WebP recommandé. Hauteur idéale ~80–120 px.')
                        ->columnSpanFull(),
                    ColorPicker::make('primary_color')
                        ->label('Couleur primaire')
                        ->hex()
                        ->helperText('Boutons, liens et identité visuelle (boutique + admin).')
                        ->required(),
                ])
                ->footerActions([
                    $this->sectionSaveAction('saveAppearance', 'Apparence enregistrée'),
                ]),
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
                ])
                ->footerActions([
                    $this->sectionSaveAction('saveIdentity', 'Identité enregistrée'),
                ]),
            Section::make('Boutique Ôhéfê — contact & communication')
                ->description('Coordonnées de la boutique.')
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
                ])
                ->footerActions([
                    $this->sectionSaveAction('saveContact', 'Contact enregistré'),
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
                ])
                ->footerActions([
                    $this->sectionSaveAction('saveOperations', 'Opérations enregistrées'),
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
                ])
                ->footerActions([
                    $this->sectionSaveAction('saveWarehouse', 'Entrepôt enregistré'),
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
                ])
                ->footerActions([
                    $this->sectionSaveAction('savePartners', 'Partenaires enregistrés'),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form'),
        ]);
    }

    private function sectionSaveAction(string $name, string $successTitle): Action
    {
        return Action::make($name)
            ->label('Enregistrer cette section')
            ->action(function (Section $component) use ($successTitle): void {
                $this->saveSection($component, $successTitle);
            });
    }

    private function saveSection(Section $component, string $successTitle): void
    {
        $oldContainer = $component->getContainer();

        $data = Schema::make($this)
            ->components([$component])
            ->statePath('data')
            ->getState();

        $component->container($oldContainer);

        $payload = $this->normalizeSectionData($data);

        if (array_key_exists('brand_logo', $payload)) {
            $payload['brand_logo'] = $this->syncBrandLogo($payload['brand_logo']);
        }

        if (array_key_exists('currency_code', $payload)) {
            $payload['currency_code'] = strtoupper((string) $payload['currency_code']);
        }

        if (array_key_exists('warehouse_country', $payload)) {
            $payload['warehouse_country'] = strtoupper((string) ($payload['warehouse_country'] ?: 'CA'));
        }

        foreach (['low_stock_threshold', 'partner_deposit_reminder_days'] as $intKey) {
            if (array_key_exists($intKey, $payload)) {
                $payload[$intKey] = (int) $payload[$intKey];
            }
        }

        foreach ([
            'partner_registration_enabled',
            'courses_enabled',
            'arrivals_enabled',
            'checkout_requires_account',
        ] as $boolKey) {
            if (array_key_exists($boolKey, $payload)) {
                $payload[$boolKey] = (bool) $payload[$boolKey];
            }
        }

        if (array_key_exists('primary_color', $payload)) {
            $color = (string) $payload['primary_color'];
            $payload['primary_color'] = preg_match('/^#[A-Fa-f0-9]{6}$/', $color) === 1
                ? strtolower($color)
                : SiteSettings::primaryColor();
        }

        SiteSettings::putMany($payload);

        Notification::make()
            ->title($successTitle)
            ->success()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeSectionData(array $data): array
    {
        $allowed = array_flip(array_keys(SiteSettings::defaults()));

        return array_intersect_key($data, $allowed);
    }

    private function syncBrandLogo(mixed $newPath): string
    {
        $path = is_array($newPath)
            ? (string) (Arr::first($newPath) ?? '')
            : (string) ($newPath ?? '');

        $previous = (string) SiteSettings::get('brand_logo');

        if ($previous !== '' && $previous !== $path && Storage::disk('public')->exists($previous)) {
            Storage::disk('public')->delete($previous);
        }

        return $path;
    }
}
