<?php

namespace App\Filament\Resources\Cargos\Pages;

use App\Actions\Cargos\AddCargoItems;
use App\Actions\Cargos\GenerateCargoCode;
use App\Filament\Resources\Cargos\CargoResource;
use App\Filament\Resources\Cargos\Support\CargoItemsRepeater;
use Filament\Resources\Pages\CreateRecord;

class CreateCargo extends CreateRecord
{
    protected static string $resource = CargoResource::class;

    /**
     * @var list<array{product_id: int, quantity_available: int, unit_price_cents: int}>
     */
    protected array $pendingItems = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingItems = CargoItemsRepeater::toLines($data['items'] ?? []);
        unset($data['items']);

        $data['code'] = app(GenerateCargoCode::class)->handle();

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->pendingItems === []) {
            return;
        }

        app(AddCargoItems::class)->handle($this->record, $this->pendingItems);
    }
}
