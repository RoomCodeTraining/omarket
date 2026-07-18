<?php

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['required', 'email', 'max:255'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Donnez un titre à votre demande.',
            'description.required' => 'Décrivez le produit recherché.',
        ];
    }

    /**
     * @return array{guest_name: string, guest_email: string, title: string, description: string, quantity: int, budget_cents: int|null}
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'guest_name' => $validated['guest_name'],
            'guest_email' => $validated['guest_email'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'quantity' => (int) $validated['quantity'],
            'budget_cents' => isset($validated['budget'])
                ? (int) round(((float) $validated['budget']) * 100)
                : null,
        ];
    }
}
