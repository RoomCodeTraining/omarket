<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates an admin with a generated password', function () {
    $this->artisan('admin:create', [
        'email' => 'ops@ohefe.test',
        '--name' => 'Ops Admin',
    ])
        ->expectsOutputToContain('Admin créé.')
        ->expectsOutputToContain('ops@ohefe.test')
        ->assertSuccessful();

    $admin = User::query()->where('email', 'ops@ohefe.test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->name)->toBe('Ops Admin')
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($admin->is_admin)->toBeTrue()
        ->and($admin->can_publish)->toBeTrue()
        ->and(Hash::check('', (string) $admin->password))->toBeFalse();
});

it('asks for the display name when --name is omitted', function () {
    $this->artisan('admin:create', [
        'email' => 'named@ohefe.test',
    ])
        ->expectsQuestion('Nom affiché', 'Roger Admin')
        ->expectsOutputToContain('Admin créé.')
        ->assertSuccessful();

    expect(User::query()->where('email', 'named@ohefe.test')->value('name'))
        ->toBe('Roger Admin');
});

it('refuses to overwrite an existing admin without --force', function () {
    User::factory()->admin()->create([
        'email' => 'existing-admin@ohefe.test',
        'password' => 'old-password',
    ]);

    $this->artisan('admin:create', [
        'email' => 'existing-admin@ohefe.test',
        '--name' => 'Existing Admin',
    ])->assertFailed();

    expect(Hash::check('old-password', User::query()->where('email', 'existing-admin@ohefe.test')->firstOrFail()->password))
        ->toBeTrue();
});

it('regenerates the password when --force is passed', function () {
    User::factory()->admin()->create([
        'email' => 'force-admin@ohefe.test',
        'password' => 'old-password',
    ]);

    $this->artisan('admin:create', [
        'email' => 'force-admin@ohefe.test',
        '--name' => 'Force Admin',
        '--force' => true,
    ])
        ->expectsOutputToContain('Admin mis à jour.')
        ->assertSuccessful();

    $admin = User::query()->where('email', 'force-admin@ohefe.test')->firstOrFail();

    expect(Hash::check('old-password', $admin->password))->toBeFalse();
});

it('refuses to take over a client email without --force', function () {
    User::factory()->create([
        'email' => 'client@ohefe.test',
        'role' => UserRole::Client,
    ]);

    $this->artisan('admin:create', [
        'email' => 'client@ohefe.test',
        '--name' => 'Client Hijack',
    ])->assertFailed();

    expect(User::query()->where('email', 'client@ohefe.test')->firstOrFail()->role)
        ->toBe(UserRole::Client);
});
