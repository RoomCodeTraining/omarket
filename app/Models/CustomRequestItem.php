<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CustomRequestItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'custom_request_id',
    'label',
    'quantity',
    'budget_cents',
    'sort_order',
])]
class CustomRequestItem extends Model
{
    /** @use HasFactory<CustomRequestItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'budget_cents' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function customRequest(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class);
    }

    public function budgetFormatted(): ?string
    {
        if ($this->budget_cents === null) {
            return null;
        }

        return number_format($this->budget_cents / 100, 2, ',', ' ').' $';
    }
}
