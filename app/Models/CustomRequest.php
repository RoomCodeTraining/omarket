<?php

namespace App\Models;

use App\Enums\CustomRequestStatus;
use Database\Factories\CustomRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'guest_name',
    'guest_email',
    'title',
    'description',
    'quantity',
    'budget_cents',
    'status',
])]
class CustomRequest extends Model
{
    /** @use HasFactory<CustomRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'budget_cents' => 'integer',
            'status' => CustomRequestStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
