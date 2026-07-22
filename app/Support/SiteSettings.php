<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

final class SiteSettings
{
    public const CACHE_KEY = 'site_settings';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'store_name' => config('app.name', 'Ôhéfê Market'),
            'store_tagline' => 'Produits ivoiriens vers le Canada',
            'contact_email' => 'hello@ohefe.test',
            'contact_phone' => '',
            'support_email' => 'support@ohefe.test',
            'currency_code' => 'CAD',
            'low_stock_threshold' => 5,
            'partner_registration_enabled' => true,
            'courses_enabled' => true,
            'arrivals_enabled' => true,
            'announcement_banner' => '',
            'default_product_unit' => 'unité',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (! Schema::hasTable('settings')) {
            return self::defaults();
        }

        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $stored = Setting::query()
                ->pluck('value', 'key')
                ->map(fn (mixed $value) => self::decode($value))
                ->all();

            return array_merge(self::defaults(), $stored);
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return $default ?? self::defaults()[$key] ?? null;
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => self::encode($value)],
        );

        self::forgetCache();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::defaults())) {
                continue;
            }

            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => self::encode($value)],
            );
        }

        self::forgetCache();
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function storeName(): string
    {
        return (string) self::get('store_name');
    }

    public static function storeTagline(): string
    {
        return (string) self::get('store_tagline');
    }

    public static function contactEmail(): string
    {
        return (string) self::get('contact_email');
    }

    public static function supportEmail(): string
    {
        return (string) self::get('support_email');
    }

    public static function contactPhone(): string
    {
        return (string) self::get('contact_phone');
    }

    public static function currencyCode(): string
    {
        return (string) self::get('currency_code');
    }

    public static function lowStockThreshold(): int
    {
        return max(0, (int) self::get('low_stock_threshold'));
    }

    public static function partnerRegistrationEnabled(): bool
    {
        return (bool) self::get('partner_registration_enabled');
    }

    public static function coursesEnabled(): bool
    {
        return (bool) self::get('courses_enabled');
    }

    public static function arrivalsEnabled(): bool
    {
        return (bool) self::get('arrivals_enabled');
    }

    public static function announcementBanner(): string
    {
        return (string) self::get('announcement_banner');
    }

    public static function defaultProductUnit(): string
    {
        return (string) self::get('default_product_unit');
    }

    private static function encode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private static function decode(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }
}
