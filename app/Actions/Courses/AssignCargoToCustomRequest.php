<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Cargo;
use App\Models\CustomRequest;
use App\Notifications\CustomRequestCargoAssignedToClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class AssignCargoToCustomRequest
{
    public function handle(CustomRequest $request, Cargo $cargo): CustomRequest
    {
        if (! $request->isValidated()) {
            throw ValidationException::withMessages([
                'cargo_id' => 'Validez d’abord la course avant d’associer un cargo.',
            ]);
        }

        $previousCargoId = $request->cargo_id;

        $request = DB::transaction(function () use ($request, $cargo) {
            $request->forceFill([
                'cargo_id' => $cargo->id,
                'cargo_assigned_at' => now(),
            ])->save();

            return $request->refresh()->loadMissing(['user', 'cargo']);
        });

        // Notify when first assigned or when cargo changes.
        if ($previousCargoId !== $cargo->id) {
            $this->notifyClient($request);
        }

        return $request;
    }

    private function notifyClient(CustomRequest $request): void
    {
        $recipient = $request->user;

        if ($recipient) {
            $recipient->notify(new CustomRequestCargoAssignedToClient($request));

            return;
        }

        if (filled($request->guest_email)) {
            Notification::route('mail', $request->guest_email)
                ->notify(new CustomRequestCargoAssignedToClient($request));
        }
    }
}
