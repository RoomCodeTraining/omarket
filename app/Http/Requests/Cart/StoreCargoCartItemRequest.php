<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class StoreCargoCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cargo_item_id' => ['required', 'integer', 'exists:cargo_items,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
