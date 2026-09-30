@extends('layouts.app')

@section('title', $country->name)

@section('content')
<section class="page-head">
    <div class="wrap">
        <a href="{{ route('medals.index') }}" class="crumb">← Tableau des médailles</a>
        <h1>{{ $country->flag('flag-lg') }} {{ $country->name }}</h1>
        <p>
            @foreach (\App\Enums\Medal::cases() as $medal)
                <span class="pill"><span class="dot dot-{{ $medal->value }}"></span> {{ $medals->where('medal', $medal)->count() }} {{ $medal->label() }}</span>
            @endforeach
        </p>
    </div>
</section>

<section class="wrap grid-2">
    <div class="card">
        <h2>Médailles</h2>
        @forelse ($medals as $result)
            <a href="{{ route('events.show', $result->event) }}" class="champion-row">
                @include('partials.medal', ['medal' => $result->medal])
                <div class="grow">
                    <div><strong>{{ $result->athlete?->fullName() ?? 'Équipe nationale' }}</strong></div>
                    <div class="muted small">{{ $result->event->sport->name }} · {{ $result->event->name }} ({{ $result->event->genderLabel() }})</div>
                </div>
            </a>
        @empty
            <p class="muted">Pas encore de médaille pour cette nation.</p>
        @endforelse
    </div>

    <aside class="card">
        <h2>Délégation ({{ $athletes->count() }})</h2>
        <table class="table">
            <tbody>
                @foreach ($athletes as $athlete)
                    <tr>
                        <td>{{ $athlete->fullName() }}</td>
                        <td class="muted small">{{ $athlete->sport->icon }} {{ $athlete->sport->name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </aside>
</section>
@endsection
