<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class RegisterClient
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function handle(array $data): User
    {
        $existing = User::query()->where('email', $data['email'])->first();

        if ($existing) {
            $message = match ($existing->role) {
                UserRole::Partner => 'Cet e-mail est déjà utilisé pour un compte partenaire. Les comptes client et partenaire sont distincts.',
                UserRole::Admin => 'Cet e-mail n’est pas disponible.',
                default => 'Un compte client existe déjà avec cet e-mail. Connectez-vous plutôt.',
            };

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Client,
            'is_admin' => false,
            'can_publish' => false,
        ]);
    }
}
