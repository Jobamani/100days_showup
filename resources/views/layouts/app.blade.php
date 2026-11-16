<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('layouts.theme-script')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|space-grotesk:500,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-page text-ink">
        <div class="min-h-screen">
            @include('layouts.navigation')

            @isset($header)
                <header class="bg-surface border-b border-line">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 font-display">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </body>
</html>