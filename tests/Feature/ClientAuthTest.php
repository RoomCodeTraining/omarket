<?php

use App\Enums\UserRole;
use App\Models\User;

it('shows the client registration page', function () {
    $this->get(route('client.register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Auth/ClientRegister'));
});

it('registers a client and redirects to the account page', function () {
    $this->post(route('client.register.store'), [
        'name' => 'Awa Client',
        'email' => 'awa.client@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('account.index'));

    $this->assertAuthenticated();

    $user = auth()->user();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Client)
        ->and($user->email)->toBe('awa.client@example.com');
});

it('rejects client registration when the email is already used by a partner', function () {
    User::factory()->partner()->create([
        'email' => 'taken.partner@example.com',
    ]);

    $this->post(route('client.register.store'), [
        'name' => 'Tentative Client',
        'email' => 'taken.partner@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
