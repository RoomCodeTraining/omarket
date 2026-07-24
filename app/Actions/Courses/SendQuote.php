<?php

namespace App\Actions\Courses;

use App\Enums\CustomRequestStatus;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Notifications\QuoteSentToClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class SendQuote
{
    public function handle(Quote $quote): Quote
    {
        if ($quote->status === QuoteStatus::Sent) {
            return $quote->loadMissing(['customRequest.user']);
        }

        if ($quote->status !== QuoteStatus::Draft) {
            throw ValidationException::withMessages([
                'quote' => 'Seuls les devis brouillon peuvent être envoyés.',
            ]);
        }

        $request = $quote->customRequest;

        if ($request === null || ! $request->isValidated()) {
            throw ValidationException::withMessages([
                'quote' => 'Validez d’abord la course avant d’envoyer un devis.',
            ]);
        }

        $quote = DB::transaction(function () use ($quote) {
            $quote->forceFill([
                'status' => QuoteStatus::Sent,
                'valid_until' => $quote->valid_until ?? now()->addDays(7),
            ])->save();

            $request = $quote->customRequest;

            if ($request && $request->status !== CustomRequestStatus::Quoted) {
                $request->forceFill([
                    'status' => CustomRequestStatus::Quoted,
                ])->save();
            }

            return $quote->refresh()->loadMissing(['customRequest.user']);
        });

        $request = $quote->customRequest;
        $recipient = $request?->user;

        if ($recipient) {
            $recipient->notify(new QuoteSentToClient($quote));
        } elseif (filled($request?->guest_email)) {
            Notification::route('mail', $request->guest_email)
                ->notify(new QuoteSentToClient($quote));
        }

        return $quote;
    }
}
