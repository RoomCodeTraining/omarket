<?php

namespace App\Http\Requests\Arrivals;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cargo_item_id' => ['required', 'integer', 'exists:cargo_items,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'guest_name.required' => 'Indiquez votre nom.',
            'guest_email.required' => 'Indiquez votre e-mail.',
            'guest_email.email' => 'E-mail invalide.',
            'quantity.required' => 'Indiquez une quantité.',
        ];
    }
}
