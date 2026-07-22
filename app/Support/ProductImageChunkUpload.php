<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductImageChunkUpload
{
    private const DISK = 'local';

    private const MAX_TOTAL_BYTES = 8 * 1024 * 1024;

    /**
     * @return array{complete: false}|array{complete: true, path: string, url: string}
     */
    public static function storeChunk(string $uploadId, int $index, int $total, string $binary): array
    {
        if (! Str::isUuid($uploadId)) {
            throw ValidationException::withMessages([
                'upload_id' => 'Identifiant d’upload invalide.',
            ]);
        }

        if ($total < 1 || $total > 256 || $index < 0 || $index >= $total) {
            throw ValidationException::withMessages([
                'chunk' => 'Morceau d’image invalide.',
            ]);
        }

        if ($binary === '' || strlen($binary) > 200_000) {
            throw ValidationException::withMessages([
                'chunk' => 'Morceau d’image trop volumineux.',
            ]);
        }

        $disk = Storage::disk(self::DISK);
        $directory = self::directory($uploadId);
        $metaPath = $directory.'/meta.json';

        if (! $disk->exists($metaPath)) {
            $disk->put($metaPath, json_encode([
                'total' => $total,
                'bytes' => 0,
            ], JSON_THROW_ON_ERROR));
        }

        /** @var array{total: int, bytes: int} $meta */
        $meta = json_decode($disk->get($metaPath) ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        if ((int) $meta['total'] !== $total) {
            throw ValidationException::withMessages([
                'total' => 'Nombre de morceaux incohérent.',
            ]);
        }

        $chunkPath = $directory.'/'.$index.'.part';
        $disk->put($chunkPath, $binary);

        $bytes = 0;
        for ($i = 0; $i < $total; $i++) {
            $part = $directory.'/'.$i.'.part';
            if (! $disk->exists($part)) {
                $disk->put($metaPath, json_encode([
                    'total' => $total,
                    'bytes' => $bytes,
                ], JSON_THROW_ON_ERROR));

                return ['complete' => false];
            }

            $bytes += strlen($disk->get($part) ?: '');

            if ($bytes > self::MAX_TOTAL_BYTES) {
                $disk->deleteDirectory($directory);

                throw ValidationException::withMessages([
                    'image' => 'L’image ne doit pas dépasser 8 Mo.',
                ]);
            }
        }

        $assembled = '';
        for ($i = 0; $i < $total; $i++) {
            $assembled .= $disk->get($directory.'/'.$i.'.part') ?: '';
        }

        $disk->deleteDirectory($directory);

        $path = ProductImageStorage::storeBinary($assembled);

        return [
            'complete' => true,
            'path' => $path,
            'url' => ProductImageStorage::url($path),
        ];
    }

    public static function directory(string $uploadId): string
    {
        return 'partner-chunks/'.$uploadId;
    }
}
