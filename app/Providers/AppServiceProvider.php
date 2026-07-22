<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // DDEV alternate HTTPS ports change; keep Livewire signed uploads + storage URLs aligned.
        $ddevUrl = env('DDEV_PRIMARY_URL');

        if (is_string($ddevUrl) && $ddevUrl !== '') {
            URL::forceRootUrl($ddevUrl);
            URL::forceScheme(str_starts_with($ddevUrl, 'https://') ? 'https' : 'http');
            config([
                'filesystems.disks.public.url' => rtrim($ddevUrl, '/').'/storage',
            ]);
        }
    }
}
