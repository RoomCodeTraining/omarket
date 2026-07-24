<?php

use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    SiteSettings::forgetCache();
});

it('persists branding settings for logo and primary color', function () {
    SiteSettings::putMany([
        'brand_logo' => 'branding/logo.png',
        'primary_color' => '#C45C26',
    ]);

    SiteSettings::forgetCache();

    expect(SiteSettings::brandLogoPath())->toBe('branding/logo.png')
        ->and(SiteSettings::brandLogoUrl())->toEndWith('storage/branding/logo.png')
        ->and(SiteSettings::primaryColor())->toBe('#c45c26');
});

it('falls back to default primary color when invalid', function () {
    SiteSettings::set('primary_color', 'not-a-color');

    expect(SiteSettings::primaryColor())->toBe('#0f2e24');
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
