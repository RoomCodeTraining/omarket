<?php

use App\Actions\Courses\AssignCargoToCustomRequest;
use App\Actions\Courses\ValidateCustomRequest;
use App\Enums\CustomRequestStatus;
use App\Models\Cargo;
use App\Models\CustomRequest;
use App\Models\User;
use App\Notifications\CustomRequestCargoAssignedToClient;
use App\Notifications\CustomRequestValidatedForTeam;
use App\Notifications\CustomRequestValidatedToClient;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

it('validates a course and notifies client and team', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $client = User::factory()->create();
    $request = CustomRequest::factory()->withSupplier()->create([
        'user_id' => $client->id,
        'guest_email' => $client->email,
        'status' => CustomRequestStatus::Submitted,
    ]);

    $validated = app(ValidateCustomRequest::class)->handle($request);

    expect($validated->status)->toBe(CustomRequestStatus::InReview)
        ->and($validated->validated_at)->not->toBeNull();

    Notification::assertSentTo($client, CustomRequestValidatedToClient::class);
    Notification::assertSentTo($admin, CustomRequestValidatedForTeam::class);
});

it('assigns a cargo to a validated course and notifies the client', function () {
    Notification::fake();

    $client = User::factory()->create();
    $cargo = Cargo::factory()->inTransit()->create([
        'code' => 'CG-TEST-0001',
        'name' => 'Cargo test courses',
    ]);
    $request = CustomRequest::factory()->validated()->create([
        'user_id' => $client->id,
        'guest_email' => $client->email,
    ]);

    $updated = app(AssignCargoToCustomRequest::class)->handle($request, $cargo);

    expect($updated->cargo_id)->toBe($cargo->id)
        ->and($updated->cargo_assigned_at)->not->toBeNull();

    Notification::assertSentTo($client, CustomRequestCargoAssignedToClient::class);
});

it('rejects cargo assignment before validation', function () {
    $cargo = Cargo::factory()->create();
    $request = CustomRequest::factory()->create([
        'validated_at' => null,
        'status' => CustomRequestStatus::Submitted,
    ]);

    app(AssignCargoToCustomRequest::class)->handle($request, $cargo);
})->throws(ValidationException::class);
