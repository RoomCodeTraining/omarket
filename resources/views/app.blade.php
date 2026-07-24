<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ \App\Support\SiteSettings::storeName() }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        @php($primary = \App\Support\SiteSettings::primaryColor())
        <style>
            :root {
                --color-forest-900: {{ $primary }};
                --color-forest-950: color-mix(in srgb, {{ $primary }} 82%, black);
                --color-forest-800: color-mix(in srgb, {{ $primary }} 88%, white);
                --color-forest-700: color-mix(in srgb, {{ $primary }} 72%, white);
                --color-forest-600: color-mix(in srgb, {{ $primary }} 58%, white);
            }
        </style>
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="min-h-screen antialiased">
        @inertia
    </body>
</html>
