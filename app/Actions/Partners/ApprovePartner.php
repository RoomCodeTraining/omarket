<?php

namespace App\Actions\Partners;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\PartnerApproved;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApprovePartner
{
    public function handle(User $partner): User
    {
        if ($partner->role !== UserRole::Partner) {
            throw ValidationException::withMessages([
                'partner' => 'Seul un compte partenaire peut être autorisé à publier.',
            ]);
        }

        $wasAlreadyApproved = $partner->can_publish;

        $partner = DB::transaction(function () use ($partner) {
            $partner->forceFill([
                'can_publish' => true,
                'partner_approved_at' => $partner->partner_approved_at ?? now(),
            ])->save();

            return $partner->refresh();
        });

        if (! $wasAlreadyApproved) {
            $partner->notify(new PartnerApproved);
        }

        return $partner;
    }
}
