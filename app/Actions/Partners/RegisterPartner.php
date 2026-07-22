<?php

namespace App\Actions\Partners;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\PartnerAccountCreated;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class RegisterPartner
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function handle(array $data): User
    {
        if (! SiteSettings::partnerRegistrationEnabled()) {
            throw ValidationException::withMessages([
                'email' => 'Les inscriptions partenaires sont temporairement fermées.',
            ]);
        }

        $existing = User::query()->where('email', $data['email'])->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'email' => 'Un compte existe déjà avec cet e-mail. Les comptes client et partenaire sont distincts.',
            ]);
        }

        $partner = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Partner,
            'is_admin' => false,
            'can_publish' => false,
            'partner_approved_at' => null,
        ]);

        $partner->notify(new PartnerAccountCreated);

        return $partner;
    }
}
