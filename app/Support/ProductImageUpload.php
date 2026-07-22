<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Forms\Components\FileUpload;

final class ProductImageUpload
{
    /**
     * Filament file upload for product images (public disk).
     */
    public static function make(string $name = 'image_path'): FileUpload
    {
        return FileUpload::make($name)
            ->label('Image')
            ->image()
            ->disk('public')
            ->directory('products')
            ->visibility('public')
            ->maxSize(8192)
            ->acceptedFileTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
            ])
            ->helperText('Choisis un JPEG/PNG depuis Téléchargements ou le Bureau — pas depuis Photos iCloud.')
            ->columnSpanFull();
    }
}
