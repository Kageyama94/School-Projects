@extends('layouts.app')

@section('content')
<section class="page-head page-head-admin">
    <div class="wrap">
        <nav class="admin-tabs">
            @foreach ([
                'admin.dashboard' => ['admin.dashboard', 'Tableau de bord'],
                'admin.events.index' => ['admin.events.*', 'Épreuves'],
                'admin.athletes.index' => ['admin.athletes.*', 'Athlètes'],
                'admin.sports.index' => ['admin.sports.*', 'Sports'],
                'admin.venues.index' => ['admin.venues.*', 'Sites'],
                'admin.countries.index' => ['admin.countries.*', 'Pays'],
                'admin.users.index' => ['admin.users.*', 'Utilisateurs'],
            ] as $routeName => [$pattern, $label])
                <a href="{{ route($routeName) }}" @class(['active' => request()->routeIs($pattern)])>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="head-row">
            <div>
                <h1>@yield('heading')</h1>
                @hasSection('subheading')
                    <p>@yield('subheading')</p>
                @endif
            </div>
            @yield('actions')
        </div>
    </div>
</section>

<section class="wrap">
    @yield('body')
</section>
@endsection
