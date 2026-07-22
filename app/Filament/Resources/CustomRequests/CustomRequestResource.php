<?php

namespace App\Filament\Resources\CustomRequests;

use App\Filament\Resources\CustomRequests\Pages\EditCustomRequest;
use App\Filament\Resources\CustomRequests\Pages\ListCustomRequests;
use App\Filament\Resources\CustomRequests\Pages\ViewCustomRequest;
use App\Filament\Resources\CustomRequests\RelationManagers\QuotesRelationManager;
use App\Filament\Resources\CustomRequests\Schemas\CustomRequestForm;
use App\Filament\Resources\CustomRequests\Schemas\CustomRequestInfolist;
use App\Filament\Resources\CustomRequests\Tables\CustomRequestsTable;
use App\Models\CustomRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CustomRequestResource extends Resource
{
    protected static ?string $model = CustomRequest::class;

    protected static ?string $slug = 'courses';

    protected static ?string $navigationLabel = 'Courses';

    protected static ?string $modelLabel = 'course';

    protected static ?string $pluralModelLabel = 'courses';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Boutique Ôhéfê';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return CustomRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            QuotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomRequests::route('/'),
            'view' => ViewCustomRequest::route('/{record}'),
            'edit' => EditCustomRequest::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
