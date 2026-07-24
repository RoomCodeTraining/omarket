<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterClientRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Indiquez votre nom.',
            'email.required' => 'Indiquez votre e-mail.',
            'email.unique' => 'Un compte existe déjà avec cet e-mail.',
            'password.required' => 'Choisissez un mot de passe.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }

    /**
     * @return array{name: string, email: string, password: string}
     */
    public function payload(): array
    {
        /** @var array{name: string, email: string, password: string} */
        return $this->safe()->only(['name', 'email', 'password']);
    }
}
