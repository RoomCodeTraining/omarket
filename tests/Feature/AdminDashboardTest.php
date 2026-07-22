<?php

use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    SiteSettings::forgetCache();
});

it('persists site settings and updates the low stock threshold', function () {
    SiteSettings::putMany([
        'store_name' => 'Ôhéfê Test',
        'low_stock_threshold' => 3,
        'partner_registration_enabled' => false,
    ]);

    SiteSettings::forgetCache();

    expect(SiteSettings::storeName())->toBe('Ôhéfê Test')
        ->and(SiteSettings::lowStockThreshold())->toBe(3)
        ->and(SiteSettings::partnerRegistrationEnabled())->toBeFalse();
});

it('blocks partner registration when disabled in settings', function () {
    Notification::fake();

    SiteSettings::putMany([
        'partner_registration_enabled' => false,
    ]);

    $this->get(route('partner.register'))->assertNotFound();

    $this->post(route('partner.register.store'), [
        'name' => 'Bloqué',
        'email' => 'blocked-partner@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});

it('lets an admin open the dashboard and settings page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/reglages')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/partner-products')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/products')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/cargos')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/courses')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/commandes')
        ->assertOk();
});
