<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.head')
    <title>@yield('title') · {{ config('app.name') }}</title>
</head>
<body>
{{-- Page autonome : ni base de données ni session, pour s'afficher même quand l'erreur vient de là. --}}
<header class="site-header">
    <div class="wrap header-inner">
        @include('partials.brand')
    </div>
</header>

<main class="error-page">
    <div class="wrap">
        <div class="card error-card">
            <div class="error-code">@yield('code')</div>
            <h1>@yield('title')</h1>
            <p class="muted">@yield('message')</p>
            <div class="error-actions">
                <a href="{{ url('/') }}" class="btn btn-primary">Retour à l'accueil</a>
                @yield('actions')
            </div>
        </div>
    </div>
</main>

<footer class="site-footer">
    <div class="wrap">
        @include('partials.rings', ['size' => 36])
    </div>
</footer>
</body>
</html>
