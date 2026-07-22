<?php

namespace App\Actions\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Support\ProductImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreatePartnerProduct
{
    /**
     * @param  array{category_id: int, name: string, description?: string|null, price_cents: int, stock_quantity: int, unit: string, image?: UploadedFile|null, image_path?: string|null, image_base64?: string|null}  $data
     */
    public function handle(User $partner, array $data): Product
    {
        if (! $partner->isPartner()) {
            throw ValidationException::withMessages([
                'product' => 'Seuls les partenaires peuvent créer des fiches produits.',
            ]);
        }

        return DB::transaction(function () use ($partner, $data) {
            $slugBase = Str::slug($data['name']);
            $slug = $slugBase;
            $suffix = 1;

            while (Product::query()->where('slug', $slug)->exists()) {
                $slug = $slugBase.'-'.$suffix;
                $suffix++;
            }

            return Product::query()->create([
                'category_id' => $data['category_id'],
                'user_id' => $partner->id,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'price_cents' => $data['price_cents'],
                'stock_quantity' => $data['stock_quantity'],
                'unit' => $data['unit'],
                'image_path' => $this->resolveImagePath($data),
                'status' => ProductStatus::Draft,
                'is_featured' => false,
            ]);
        });
    }

    /**
     * @param  array{image?: UploadedFile|null, image_path?: string|null, image_base64?: string|null}  $data
     */
    private function resolveImagePath(array $data): ?string
    {
        if (($data['image'] ?? null) instanceof UploadedFile) {
            return $data['image']->store('products', 'public');
        }

        $path = $data['image_path'] ?? null;

        if (is_string($path) && $path !== '') {
            if (! preg_match('/^products\/[A-Za-z0-9._-]+$/', $path)) {
                throw ValidationException::withMessages([
                    'image' => 'Chemin d’image invalide.',
                ]);
            }

            if (! Storage::disk('public')->exists($path)) {
                throw ValidationException::withMessages([
                    'image' => 'Image introuvable. Rechargez le fichier puis réessayez.',
                ]);
            }

            return $path;
        }

        $dataUrl = $data['image_base64'] ?? null;

        if (! is_string($dataUrl) || $dataUrl === '') {
            return null;
        }

        $comma = strpos($dataUrl, ',');
        $payload = $comma === false ? $dataUrl : substr($dataUrl, $comma + 1);
        $binary = base64_decode($payload, true);

        if ($binary === false || $binary === '') {
            throw ValidationException::withMessages([
                'image' => 'Impossible de décoder l’image.',
            ]);
        }

        return ProductImageStorage::storeBinary($binary);
    }
}
