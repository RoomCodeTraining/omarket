<?php

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomRequestRequest extends FormRequest
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
        $user = $this->user();

        return [
            'guest_name' => [$user ? 'nullable' : 'required', 'string', 'max:120'],
            'guest_email' => [$user ? 'nullable' : 'required', 'email', 'max:255'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'guest_name.required' => 'Indiquez votre nom.',
            'guest_email.required' => 'Indiquez votre e-mail.',
            'title.required' => 'Donnez un titre à votre demande.',
            'description.required' => 'Décrivez le produit recherché.',
        ];
    }

    /**
     * @return array{guest_name: string, guest_email: string, title: string, description: string, quantity: int, budget_cents: int|null}
     */
    public function payload(): array
    {
        $user = $this->user();
        $validated = $this->validated();

        return [
            'guest_name' => $user?->name ?? (string) $validated['guest_name'],
            'guest_email' => $user?->email ?? (string) $validated['guest_email'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'quantity' => (int) $validated['quantity'],
            'budget_cents' => isset($validated['budget'])
                ? (int) round(((float) $validated['budget']) * 100)
                : null,
        ];
    }
}
