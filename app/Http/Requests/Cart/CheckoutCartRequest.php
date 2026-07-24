<?php

namespace App\Http\Requests\Cart;

use App\Support\SiteSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class CheckoutCartRequest extends FormRequest
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
        $requiresAccount = SiteSettings::checkoutRequiresAccount();

        return [
            'guest_name' => [$user ? 'nullable' : 'required', 'string', 'max:120'],
            'guest_email' => [$user ? 'nullable' : 'required', 'email', 'max:255'],
            'shipping_phone' => ['nullable', 'string', 'max:40'],
            'shipping_line1' => ['required', 'string', 'max:160'],
            'shipping_line2' => ['nullable', 'string', 'max:160'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_province' => ['required', 'string', 'max:80'],
            'shipping_postal_code' => ['required', 'string', 'max:20'],
            'shipping_country' => ['nullable', 'string', 'size:2'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'create_account' => ['sometimes', 'boolean'],
            'password' => $user
                ? ['nullable']
                : [
                    ($requiresAccount || $this->boolean('create_account')) ? 'required' : 'nullable',
                    'confirmed',
                    Password::defaults(),
                ],
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
            'guest_email.email' => 'L’e-mail n’est pas valide.',
            'shipping_line1.required' => 'Indiquez l’adresse de livraison.',
            'shipping_city.required' => 'Indiquez la ville.',
            'shipping_province.required' => 'Indiquez la province.',
            'shipping_postal_code.required' => 'Indiquez le code postal.',
            'password.required_if' => 'Choisissez un mot de passe pour créer votre compte.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('create_account') && $this->user() !== null) {
                $validator->errors()->add('create_account', 'Vous êtes déjà connecté.');
            }

            if (
                SiteSettings::checkoutRequiresAccount()
                && $this->user() === null
                && ! $this->boolean('create_account')
            ) {
                $validator->errors()->add(
                    'create_account',
                    'Un compte client est requis pour valider une commande. Connectez-vous ou créez un compte.',
                );
            }
        });
    }

    /**
     * @return array{
     *     guest_name: string,
     *     guest_email: string,
     *     shipping_phone: string|null,
     *     shipping_line1: string,
     *     shipping_line2: string|null,
     *     shipping_city: string,
     *     shipping_province: string,
     *     shipping_postal_code: string,
     *     shipping_country: string,
     *     notes: string|null,
     *     create_account: bool,
     *     password: string|null
     * }
     */
    public function payload(): array
    {
        $user = $this->user();
        $validated = $this->validated();

        return [
            'guest_name' => $user?->name ?? (string) $validated['guest_name'],
            'guest_email' => $user?->email ?? (string) $validated['guest_email'],
            'shipping_phone' => $validated['shipping_phone'] ?? null,
            'shipping_line1' => (string) $validated['shipping_line1'],
            'shipping_line2' => $validated['shipping_line2'] ?? null,
            'shipping_city' => (string) $validated['shipping_city'],
            'shipping_province' => (string) $validated['shipping_province'],
            'shipping_postal_code' => strtoupper((string) $validated['shipping_postal_code']),
            'shipping_country' => strtoupper((string) ($validated['shipping_country'] ?? 'CA')),
            'notes' => $validated['notes'] ?? null,
            'create_account' => $user === null && (
                $this->boolean('create_account')
                || SiteSettings::checkoutRequiresAccount()
            ),
            'password' => isset($validated['password']) && is_string($validated['password'])
                ? $validated['password']
                : null,
        ];
    }
}
