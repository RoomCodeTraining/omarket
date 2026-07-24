<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Enums\CustomRequestStatus;
use App\Models\CustomRequest;
use App\Models\User;
use App\Notifications\CustomRequestValidatedForTeam;
use App\Notifications\CustomRequestValidatedToClient;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class ValidateCustomRequest
{
    public function handle(CustomRequest $request): CustomRequest
    {
        if ($request->isValidated()) {
            return $request->loadMissing(['user']);
        }

        if ($request->status !== CustomRequestStatus::Submitted) {
            throw ValidationException::withMessages([
                'status' => 'Seules les courses soumises peuvent être validées.',
            ]);
        }

        $request = DB::transaction(function () use ($request) {
            $request->forceFill([
                'status' => CustomRequestStatus::InReview,
                'validated_at' => now(),
            ])->save();

            return $request->refresh()->loadMissing(['user']);
        });

        $this->notifyClient($request);
        $this->notifyTeam($request);

        return $request;
    }

    private function notifyClient(CustomRequest $request): void
    {
        $recipient = $request->user;

        if ($recipient) {
            $recipient->notify(new CustomRequestValidatedToClient($request));

            return;
        }

        if (filled($request->guest_email)) {
            Notification::route('mail', $request->guest_email)
                ->notify(new CustomRequestValidatedToClient($request));
        }
    }

    private function notifyTeam(CustomRequest $request): void
    {
        $admins = User::query()->admins()->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new CustomRequestValidatedForTeam($request));
        }

        $support = SiteSettings::supportEmail();

        if ($support !== '' && $admins->doesntContain(fn (User $admin) => $admin->email === $support)) {
            Notification::route('mail', $support)
                ->notify(new CustomRequestValidatedForTeam($request));
        }
    }
}
