@extends('layouts.app')

@section('title', 'Sites')

@section('content')
<section class="page-head">
    <div class="wrap">
        <h1>Les sites olympiques</h1>
        <p>Où se déroulent les épreuves.</p>
    </div>
</section>

<section class="wrap venue-grid">
    @foreach ($venues as $venue)
        <div class="card venue-card">
            <h3>{{ $venue->name }}</h3>
            <p class="muted">📍 {{ $venue->city }} · @number($venue->capacity) places</p>
            <ul>
                @foreach ($venue->events as $event)
                    <li @class(['venue-cancelled' => $event->isCancelled()])>
                        <a href="{{ route('events.show', $event) }}">{{ $event->fullTitle() }}</a>
                        @if ($event->isCancelled())
                            <span class="status status-cancelled">Annulée</span>
                        @else
                            <span class="muted small">{{ $event->starts_at->translatedFormat('j M') }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</section>
@endsection
