<?php

namespace App\Actions\Partners;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class RevokePartnerPublish
{
    public function handle(User $partner): User
    {
        if ($partner->role !== UserRole::Partner) {
            throw ValidationException::withMessages([
                'partner' => 'Seul un compte partenaire peut être révoqué.',
            ]);
        }

        $partner->forceFill([
            'can_publish' => false,
            'partner_approved_at' => null,
        ])->save();

        return $partner->refresh();
    }
}
