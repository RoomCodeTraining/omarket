<?php

namespace App\Http\Requests\Cart;

use App\Support\SiteSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class CheckoutCargoCartRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:2000'],
            'create_account' => ['sometimes', 'boolean'],
            'password' => [
                'nullable',
                $requiresAccount && $user === null ? 'required' : 'required_if:create_account,1',
                'required_if:create_account,true',
                'confirmed',
                Password::defaults(),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                SiteSettings::checkoutRequiresAccount()
                && $this->user() === null
                && ! $this->boolean('create_account')
            ) {
                $validator->errors()->add(
                    'create_account',
                    'Un compte client est requis pour valider une réservation.',
                );
            }
        });
    }

    /**
     * @return array{
     *     guest_name: string,
     *     guest_email: string,
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
            'notes' => $validated['notes'] ?? null,
            'create_account' => $this->boolean('create_account')
                || (SiteSettings::checkoutRequiresAccount() && $user === null),
            'password' => isset($validated['password']) && is_string($validated['password'])
                ? $validated['password']
                : null,
        ];
    }
}
