<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.head')
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
</head>
<body>

<header class="site-header">
    <div class="wrap header-inner">
        @include('partials.brand')

        <nav class="main-nav">
            <a href="{{ route('events.index') }}" @class(['active' => request()->routeIs('events.*')])>Épreuves</a>
            <a href="{{ route('sports.index') }}" @class(['active' => request()->routeIs('sports.*')])>Sports</a>
            <a href="{{ route('medals.index') }}" @class(['active' => request()->routeIs('medals.*', 'countries.*')])>Médailles</a>
            <a href="{{ route('venues.index') }}" @class(['active' => request()->routeIs('venues.*')])>Sites</a>
        </nav>

        <div class="user-nav">
            @auth
                {{-- Sur téléphone, seules les icônes restent visibles (.label masqué) pour que tout tienne. --}}
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-ghost" aria-label="Administration">⚙️<span class="label"> Admin</span></a>
                @endif
                <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-ghost" aria-label="Mes billets">🎟️<span class="label"> Mes billets</span></a>
                <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-ghost" aria-label="Mon profil">👤<span class="label"> {{ auth()->user()->name }}</span></a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-light" aria-label="Déconnexion">⏻<span class="label"> Déconnexion</span></button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-sm btn-ghost">Connexion</a>
                <a href="{{ route('register') }}" class="btn btn-sm btn-light">Inscription</a>
            @endauth
        </div>
    </div>
</header>

<main>
    @if (session('success'))
        <div class="wrap"><div class="alert alert-success">{{ session('success') }}</div></div>
    @endif
    @php
        // Les erreurs affichées sous un champ (composant <x-error>) ne sont pas répétées ici.
        $inline = request()->attributes->get('inline_errors', []);
        $globalErrors = collect($errors->getMessages())->except($inline)->flatten();
        $fieldErrors = collect($errors->getMessages())->only($inline)->isNotEmpty();
    @endphp
    @if ($globalErrors->isNotEmpty() || $fieldErrors)
        <div class="wrap">
            <div class="alert alert-error" role="alert">
                @foreach ($globalErrors as $error)
                    <div>{{ $error }}</div>
                @endforeach
                @if ($fieldErrors)
                    <div>Le formulaire contient des erreurs, voir les champs signalés ci-dessous.</div>
                @endif
            </div>
        </div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    <div class="wrap">
        @include('partials.rings', ['size' => 36])
        <p>Projet scolaire Laravel — données de démonstration (athlètes et résultats fictifs).</p>
    </div>
</footer>

@stack('scripts')
</body>
</html>
