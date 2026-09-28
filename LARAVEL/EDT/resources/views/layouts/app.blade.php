<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name') }}</title>
        <!-- Styles / Scripts (fichiers locaux, aucune dépendance externe) -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <script defer src="{{ asset('js/alpine.min.js') }}"></script>
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                <x-flash-messages />

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
