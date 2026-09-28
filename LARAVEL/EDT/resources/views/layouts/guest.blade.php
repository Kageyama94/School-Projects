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
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <div>
                <a href="/" class="flex flex-col items-center gap-2">
                    <x-application-logo class="w-16 h-16 text-gray-500" />
                    <span class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ config('app.name') }}</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
