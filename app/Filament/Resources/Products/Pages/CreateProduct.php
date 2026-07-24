<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Support\UniqueSlug;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = null;
        $data['cargo_id'] = null;
        $data['status'] ??= ProductStatus::Published->value;
        $data['listed_in_shop'] = (bool) ($data['listed_in_shop'] ?? true);
        $data['slug'] = UniqueSlug::for(Product::class, (string) $data['name']);

        return $data;
    }
}
