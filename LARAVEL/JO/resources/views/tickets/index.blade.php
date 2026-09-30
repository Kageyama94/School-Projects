@extends('layouts.app')

@section('title', 'Mes billets')

@section('content')
<section class="page-head">
    <div class="wrap">
        <h1>Mes billets</h1>
        <p>{{ $upcoming->sum('quantity') }} place(s) réservée(s) pour les épreuves à venir · @euros($upcoming->sum(fn ($ticket) => $ticket->total()))</p>
    </div>
</section>

<section class="wrap">
    <h2>À venir</h2>
    <div class="ticket-list">
        @forelse ($upcoming as $ticket)
            @include('partials.ticket-card', ['status' => 'upcoming'])
        @empty
            <div class="empty">
                Aucun billet à venir.
                <a href="{{ route('events.index', ['status' => 'upcoming']) }}">Découvrir les épreuves →</a>
            </div>
        @endforelse
    </div>

    @if ($refunded->isNotEmpty())
        <h2>Remboursés</h2>
        <p class="muted small">Billets annulés par vous, ou épreuves annulées par l'organisation.</p>
        <div class="ticket-list">
            @foreach ($refunded as $ticket)
                @include('partials.ticket-card', ['status' => 'refunded'])
            @endforeach
        </div>
    @endif

    @if ($past->isNotEmpty())
        <h2>Épreuves passées</h2>
        <div class="ticket-list">
            @foreach ($past as $ticket)
                @include('partials.ticket-card', ['status' => 'past'])
            @endforeach
        </div>
    @endif
</section>
@endsection
