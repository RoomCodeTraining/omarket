<?php

declare(strict_types=1);

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

final class UpdatePartnerProduct
{
    /**
     * @param  array{category_id: int, name: string, description?: string|null, price_cents: int, stock_quantity: int, unit: string, image?: UploadedFile|null, image_path?: string|null, image_base64?: string|null}  $data
     */
    public function handle(Product $product, User $partner, array $data): Product
    {
        if (! $product->isOwnedBy($partner)) {
            throw ValidationException::withMessages([
                'product' => 'Vous ne pouvez modifier que vos propres produits.',
            ]);
        }

        return DB::transaction(function () use ($product, $data) {
            $slugBase = Str::slug($data['name']);
            $slug = $slugBase;
            $suffix = 1;

            while (
                Product::query()
                    ->where('slug', $slug)
                    ->where('id', '!=', $product->id)
                    ->exists()
            ) {
                $slug = $slugBase.'-'.$suffix;
                $suffix++;
            }

            $imagePath = $this->resolveImagePath($product, $data);

            $status = $product->status;

            // Editing a product awaiting review sends it back to draft.
            if ($status === ProductStatus::PendingReview) {
                $status = ProductStatus::Draft;
            }

            $product->forceFill([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'price_cents' => $data['price_cents'],
                'stock_quantity' => $data['stock_quantity'],
                'unit' => $data['unit'],
                'image_path' => $imagePath,
                'status' => $status,
            ])->save();

            return $product->refresh();
        });
    }

    /**
     * @param  array{image?: UploadedFile|null, image_path?: string|null, image_base64?: string|null}  $data
     */
    private function resolveImagePath(Product $product, array $data): ?string
    {
        if (($data['image'] ?? null) instanceof UploadedFile) {
            $this->deleteStoredImage($product->image_path);

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

            if ($product->image_path !== $path) {
                $this->deleteStoredImage($product->image_path);
            }

            return $path;
        }

        $dataUrl = $data['image_base64'] ?? null;

        if (is_string($dataUrl) && $dataUrl !== '') {
            $comma = strpos($dataUrl, ',');
            $payload = $comma === false ? $dataUrl : substr($dataUrl, $comma + 1);
            $binary = base64_decode($payload, true);

            if ($binary === false || $binary === '') {
                throw ValidationException::withMessages([
                    'image' => 'Impossible de décoder l’image.',
                ]);
            }

            $this->deleteStoredImage($product->image_path);

            return ProductImageStorage::storeBinary($binary);
        }

        return $product->image_path;
    }

    private function deleteStoredImage(?string $path): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        if (! str_starts_with($path, 'products/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
