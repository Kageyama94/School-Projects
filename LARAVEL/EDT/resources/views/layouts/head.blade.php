<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- Pas d'aperçu depuis le cache de Turbo : Alpine réinitialiserait une copie déjà rendue (listes x-for en double). --}}
<meta name="turbo-cache-control" content="no-cache">
{{-- Pas de requête vers le serveur au simple survol d'un lien. --}}
<meta name="turbo-prefetch" content="false">
@stack('head')

<title>{{ $title ? $title.' — ' : '' }}{{ config('app.name') }}</title>
<!-- Styles / Scripts (fichiers locaux, aucune dépendance externe) -->
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
{{-- data-turbo-track : si l'un de ces fichiers change, Turbo recharge la page entière. --}}
<link rel="stylesheet" href="{{ asset('css/app.css') }}" data-turbo-track="reload">
{{-- Turbo remplace le contenu de la page sans la recharger ; les scripts « defer » s'exécutent dans cet ordre. --}}
<script defer src="{{ asset('js/turbo.min.js') }}" data-turbo-track="reload"></script>
<script defer src="{{ asset('js/teacher-scheduler.js') }}" data-turbo-track="reload"></script>
<script defer src="{{ asset('js/alpine.min.js') }}" data-turbo-track="reload"></script>
<style>
    [x-cloak] { display: none !important; }
    /* Fond sur la page elle-même : pas d'éclair blanc entre deux pages en mode sombre. */
    html { color-scheme: light dark; background-color: rgb(243 244 246); }
    @media (prefers-color-scheme: dark) {
        html { background-color: rgb(17 24 39); }
    }
</style>
