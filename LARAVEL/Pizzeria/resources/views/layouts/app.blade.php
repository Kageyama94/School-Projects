<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.head')
</head>
<body>

<header>
    @include('partials.brand')

    @auth
        <form action="{{ route('logout') }}" method="post" class="logout-form">
            @csrf
            <input type="submit" value="Se déconnecter">
        </form>
    @endauth
</header>

<div class="container">
    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">
            @if ($errors->count() === 1)
                {{ $errors->first() }}
            @else
                <ul style="list-style:none">
                    @foreach ($errors->all() as $erreur)
                        <li>{{ $erreur }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @yield('content')
</div>

</body>
</html>
