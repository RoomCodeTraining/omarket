<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'name',
    'slug',
    'description',
    'price_cents',
    'stock_quantity',
    'unit',
    'status',
    'image_path',
    'is_featured',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    public const DEFAULT_IMAGE_PATH = 'images/products/default.svg';

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'stock_quantity' => 'integer',
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
        ];
    }

    public function imageUrl(): string
    {
        if (filled($this->image_path)) {
            return asset(ltrim((string) $this->image_path, '/'));
        }

        return asset(self::DEFAULT_IMAGE_PATH);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function cargoItems(): HasMany
    {
        return $this->hasMany(CargoItem::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function priceFormatted(): string
    {
        return number_format($this->price_cents / 100, 2, ',', ' ').' $';
    }

    public function isAvailableLocally(): bool
    {
        return $this->status === ProductStatus::Published && $this->stock_quantity > 0;
    }
}
