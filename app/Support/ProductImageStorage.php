<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductImageStorage
{
    public const SESSION_KEY = 'partner.uploaded_product_image';

    public const MAX_BYTES = 8 * 1024 * 1024;

    public static function extensionFromBinary(string $binary): ?string
    {
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'jpg';
        }

        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }

        if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
            return 'gif';
        }

        if (str_starts_with($binary, 'RIFF') && str_contains(substr($binary, 0, 16), 'WEBP')) {
            return 'webp';
        }

        return null;
    }

    public static function storeBinary(string $binary): string
    {
        if ($binary === '' || strlen($binary) > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'image' => 'L’image doit faire entre 1 octet et 8 Mo.',
            ]);
        }

        $extension = self::extensionFromBinary($binary);

        if ($extension === null) {
            throw ValidationException::withMessages([
                'image' => 'Format invalide. Envoyez un JPEG, PNG, WebP ou GIF.',
            ]);
        }

        $path = 'products/'.Str::uuid()->toString().'.'.$extension;
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public static function url(string $path): string
    {
        return url('storage/'.$path);
    }
}
