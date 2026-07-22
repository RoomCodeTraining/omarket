<?php

namespace App\Filament\Resources\PartnerProducts\Pages;

use App\Filament\Resources\PartnerProducts\PartnerProductResource;
use Filament\Resources\Pages\ListRecords;

class ListPartnerProducts extends ListRecords
{
    protected static string $resource = PartnerProductResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
