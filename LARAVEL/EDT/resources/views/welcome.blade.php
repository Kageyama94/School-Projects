<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'EDT') }}</title>
        <!-- Styles (fichier local, aucune dépendance externe) -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        <div class="min-h-screen flex flex-col items-center justify-center px-6">
            <div class="max-w-xl w-full text-center">
                <h1 class="text-4xl font-semibold mb-4">{{ config('app.name', 'EDT') }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mb-8">
                    Gestion des emplois du temps.
                </p>

                @if (Route::has('login'))
                    <div class="flex items-center justify-center gap-4">
                        @auth
                            <a
                                href="{{ url('/dashboard') }}"
                                class="px-5 py-2 rounded-md bg-gray-900 text-white dark:bg-white dark:text-gray-900 text-sm font-medium"
                            >
                                Tableau de bord
                            </a>
                        @else
                            <a
                                href="{{ route('login') }}"
                                class="px-5 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm font-medium"
                            >
                                Connexion
                            </a>

                            @if (Route::has('register'))
                                <a
                                    href="{{ route('register') }}"
                                    class="px-5 py-2 rounded-md bg-gray-900 text-white dark:bg-white dark:text-gray-900 text-sm font-medium"
                                >
                                    Inscription
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </body>
</html>
