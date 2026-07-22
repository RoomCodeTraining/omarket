<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Forms\Components\FileUpload;

final class ProductImageUpload
{
    /**
     * Filament file upload for product images (public disk + legacy public/ assets).
     */
    public static function make(string $name = 'image_path'): FileUpload
    {
        return FileUpload::make($name)
            ->label('Image')
            ->image()
            ->columnSpanFull();
    }
}
