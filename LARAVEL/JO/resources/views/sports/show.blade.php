@extends('layouts.app')

@section('title', $sport->name)

@section('content')
<section class="page-head">
    <div class="wrap">
        <a href="{{ route('sports.index') }}" class="crumb">← Tous les sports</a>
        <h1>{{ $sport->icon }} {{ $sport->name }}</h1>
        <p>{{ $sport->description }}</p>
    </div>
</section>

<section class="wrap grid-2">
    <div>
        <h2>Épreuves</h2>
        <div class="stack">
            @foreach ($events as $event)
                @include('partials.event-card')
            @endforeach
        </div>
    </div>

    <aside class="card">
        <h2>Athlètes engagés</h2>
        <table class="table">
            <tbody>
                @foreach ($athletes as $athlete)
                    <tr>
                        <td>{{ $athlete->country->flag('flag-sm') }}</td>
                        <td>{{ $athlete->fullName() }}</td>
                        <td class="muted small">{{ $athlete->gender->value }}</td>
                        <td class="right"><a href="{{ route('countries.show', $athlete->country) }}" class="small">{{ $athlete->country->code }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </aside>
</section>
@endsection
