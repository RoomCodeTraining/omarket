<?php

namespace App\Http\Requests\Partners;

use App\Support\ProductImageStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorePartnerProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPartner() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'cargo_id' => ['required', 'integer', 'exists:cargos,id'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:40'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:8192'],
            'image_base64' => ['nullable', 'string', 'max:12000000'],
        ];
    }

    /**
     * @return array{category_id: int, cargo_id: int, name: string, description: string|null, price_cents: int, stock_quantity: int, unit: string, image: UploadedFile|null, image_path: string|null, image_base64: string|null}
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $sessionPath = $this->session()->pull(ProductImageStorage::SESSION_KEY);
        $imagePath = null;

        if (is_string($sessionPath) && $sessionPath !== '' && Storage::disk('public')->exists($sessionPath)) {
            $imagePath = $sessionPath;
        }

        return [
            'category_id' => (int) $validated['category_id'],
            'cargo_id' => (int) $validated['cargo_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price_cents' => (int) round(((float) $validated['price']) * 100),
            'stock_quantity' => (int) $validated['stock_quantity'],
            'unit' => $validated['unit'],
            'image' => $this->file('image'),
            'image_path' => $imagePath,
            'image_base64' => isset($validated['image_base64']) && is_string($validated['image_base64']) && $validated['image_base64'] !== ''
                ? $validated['image_base64']
                : null,
        ];
    }
}
