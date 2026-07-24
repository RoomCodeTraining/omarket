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
    'user_id',
    'cargo_id',
    'name',
    'slug',
    'description',
    'price_cents',
    'stock_quantity',
    'unit',
    'status',
    'image_path',
    'is_featured',
    'listed_in_shop',
    'warehouse_deposited_at',
    'warehouse_deposited_quantity',
    'deposit_reminder_sent_at',
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
            'listed_in_shop' => 'boolean',
            'warehouse_deposited_at' => 'datetime',
            'warehouse_deposited_quantity' => 'integer',
            'deposit_reminder_sent_at' => 'datetime',
        ];
    }

    public function imageUrl(): string
    {
        // Always use url() so DDEV_PRIMARY_URL / URL::forceRootUrl apply.
        // Storage::disk('public')->url() is baked from APP_URL and goes stale when the port changes.
        if (! filled($this->image_path)) {
            return url(self::DEFAULT_IMAGE_PATH);
        }

        $path = ltrim((string) $this->image_path, '/');

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Seeded / static assets under public/
        if (str_starts_with($path, 'images/')) {
            return url($path);
        }

        // Filament / partner uploads on the public disk → /storage/...
        return url('storage/'.$path);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function cargoItems(): HasMany
    {
        return $this->hasMany(CargoItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published);
    }

    public function scopeListedInShop(Builder $query): Builder
    {
        return $query->where('listed_in_shop', true)->whereNull('user_id');
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::PendingReview);
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
        return $this->status === ProductStatus::Published
            && $this->listed_in_shop
            && $this->user_id === null
            && $this->stock_quantity > 0;
    }

    public function isWarehouseDeposited(): bool
    {
        return $this->warehouse_deposited_at !== null;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id !== null && $this->user_id === $user->id;
    }
}
