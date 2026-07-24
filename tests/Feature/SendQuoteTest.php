<?php

use App\Actions\Courses\SendQuote;
use App\Enums\CustomRequestStatus;
use App\Enums\QuoteStatus;
use App\Models\CustomRequest;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\QuoteSentToClient;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

it('sends a draft quote to the client and marks the course as quoted', function () {
    Notification::fake();

    $client = User::factory()->create();
    $request = CustomRequest::factory()->validated()->create([
        'user_id' => $client->id,
        'guest_email' => $client->email,
    ]);
    $quote = Quote::factory()->create([
        'custom_request_id' => $request->id,
        'status' => QuoteStatus::Draft,
        'amount_cents' => 5000,
        'message' => 'Devis tout compris.',
    ]);

    $sent = app(SendQuote::class)->handle($quote);

    expect($sent->status)->toBe(QuoteStatus::Sent)
        ->and($request->fresh()->status)->toBe(CustomRequestStatus::Quoted);

    Notification::assertSentTo($client, QuoteSentToClient::class);
});

it('emails a guest address when the course has no linked user', function () {
    Notification::fake();

    $request = CustomRequest::factory()->validated()->create([
        'user_id' => null,
        'guest_name' => 'Invité Devis',
        'guest_email' => 'invite.devis@example.com',
    ]);
    $quote = Quote::factory()->create([
        'custom_request_id' => $request->id,
        'status' => QuoteStatus::Draft,
        'amount_cents' => 3200,
    ]);

    app(SendQuote::class)->handle($quote);

    Notification::assertSentOnDemand(QuoteSentToClient::class);
});

it('refuses to send a quote before the course is validated', function () {
    $client = User::factory()->create();
    $request = CustomRequest::factory()->create([
        'user_id' => $client->id,
        'status' => CustomRequestStatus::Submitted,
        'validated_at' => null,
    ]);
    $quote = Quote::factory()->create([
        'custom_request_id' => $request->id,
        'status' => QuoteStatus::Draft,
    ]);

    app(SendQuote::class)->handle($quote);
})->throws(ValidationException::class);
