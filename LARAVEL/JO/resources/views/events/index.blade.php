@extends('layouts.app')

@section('title', 'Épreuves')

@section('content')
<section class="page-head">
    <div class="wrap">
        <h1>Calendrier des épreuves</h1>
        <p>Retrouvez toutes les sessions, jour par jour.</p>
    </div>
</section>

<section class="wrap">
    <form method="GET" class="filters card">
        <label>
            Sport
            <select name="sport">
                <option value="">Tous les sports</option>
                @foreach ($sports as $sport)
                    <option value="{{ $sport->id }}" @selected(($filters['sport'] ?? null) == $sport->id)>{{ $sport->icon }} {{ $sport->name }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Date
            <input type="date" name="date" value="{{ $filters['date'] ?? '' }}">
        </label>
        <label>
            Statut
            <select name="status">
                <option value="">Toutes</option>
                <option value="upcoming" @selected(($filters['status'] ?? null) === 'upcoming')>À venir</option>
                <option value="past" @selected(($filters['status'] ?? null) === 'past')>Terminées</option>
                <option value="cancelled" @selected(($filters['status'] ?? null) === 'cancelled')>Annulées</option>
            </select>
        </label>
        <div class="filters-actions">
            <button class="btn btn-primary">Filtrer</button>
            <a href="{{ route('events.index') }}" class="btn btn-ghost-dark">Réinitialiser</a>
        </div>
    </form>

    @forelse ($eventsByDay as $day => $events)
        <h2 class="day-title">{{ \Illuminate\Support\Carbon::parse($day)->translatedFormat('l j F Y') }}</h2>
        <div class="stack">
            @foreach ($events as $event)
                @include('partials.event-card')
            @endforeach
        </div>
    @empty
        <div class="empty">Aucune épreuve ne correspond à ces critères.</div>
    @endforelse
</section>
@endsection
