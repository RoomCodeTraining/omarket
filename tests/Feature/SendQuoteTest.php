<?php

use App\Actions\Courses\SendQuote;
use App\Enums\CustomRequestStatus;
use App\Enums\QuoteStatus;
use App\Models\CustomRequest;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\QuoteSentToClient;
use Illuminate\Support\Facades\Notification;

it('sends a draft quote to the client and marks the course as quoted', function () {
    Notification::fake();

    $client = User::factory()->create();
    $request = CustomRequest::factory()->create([
        'user_id' => $client->id,
        'guest_email' => $client->email,
        'status' => CustomRequestStatus::Submitted,
    ]);
    $quote = Quote::factory()->create([
        'custom_request_id' => $request->id,
        'status' => QuoteStatus::Draft,
        'amount_cents' => 5000,
    ]);

    $sent = app(SendQuote::class)->handle($quote);

    expect($sent->status)->toBe(QuoteStatus::Sent)
        ->and($request->fresh()->status)->toBe(CustomRequestStatus::Quoted);

    Notification::assertSentTo($client, QuoteSentToClient::class);
});
