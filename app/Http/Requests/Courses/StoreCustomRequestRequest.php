<?php

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'has_supplier' => ['required', 'boolean'],
            'supplier_name' => ['nullable', 'required_if:has_supplier,1', 'required_if:has_supplier,true', 'string', 'max:160'],
            'supplier_contact' => ['nullable', 'string', 'max:255'],
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
            'has_supplier.required' => 'Indiquez si vous avez un fournisseur.',
            'supplier_name.required_if' => 'Indiquez le nom du fournisseur.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('has_supplier') && blank($this->input('supplier_name'))) {
                $validator->errors()->add('supplier_name', 'Indiquez le nom du fournisseur.');
            }
        });
    }

    /**
     * @return array{
     *     guest_name: string,
     *     guest_email: string,
     *     title: string,
     *     description: string,
     *     quantity: int,
     *     budget_cents: int|null,
     *     has_supplier: bool,
     *     supplier_name: string|null,
     *     supplier_contact: string|null
     * }
     */
    public function payload(): array
    {
        $user = $this->user();
        $validated = $this->validated();
        $hasSupplier = $this->boolean('has_supplier');

        return [
            'guest_name' => $user?->name ?? (string) $validated['guest_name'],
            'guest_email' => $user?->email ?? (string) $validated['guest_email'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'quantity' => (int) $validated['quantity'],
            'budget_cents' => isset($validated['budget'])
                ? (int) round(((float) $validated['budget']) * 100)
                : null,
            'has_supplier' => $hasSupplier,
            'supplier_name' => $hasSupplier ? ($validated['supplier_name'] ?? null) : null,
            'supplier_contact' => $hasSupplier ? ($validated['supplier_contact'] ?? null) : null,
        ];
    }
}
