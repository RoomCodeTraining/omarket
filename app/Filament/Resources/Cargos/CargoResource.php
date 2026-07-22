<?php

namespace App\Filament\Resources\Cargos;

use App\Filament\Resources\Cargos\Pages\CreateCargo;
use App\Filament\Resources\Cargos\Pages\EditCargo;
use App\Filament\Resources\Cargos\Pages\ListCargos;
use App\Filament\Resources\Cargos\Pages\ViewCargo;
use App\Filament\Resources\Cargos\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Cargos\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\Cargos\RelationManagers\ReservationsRelationManager;
use App\Filament\Resources\Cargos\Schemas\CargoForm;
use App\Filament\Resources\Cargos\Schemas\CargoInfolist;
use App\Filament\Resources\Cargos\Tables\CargosTable;
use App\Models\Cargo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CargoResource extends Resource
{
    protected static ?string $model = Cargo::class;

    protected static ?string $navigationLabel = 'Arrivages';

    protected static ?string $modelLabel = 'arrivage';

    protected static ?string $pluralModelLabel = 'arrivages';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Boutique Ôhéfê';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return CargoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CargoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CargosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            OrdersRelationManager::class,
            ReservationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCargos::route('/'),
            'create' => CreateCargo::route('/create'),
            'view' => ViewCargo::route('/{record}'),
            'edit' => EditCargo::route('/{record}/edit'),
        ];
    }
}
