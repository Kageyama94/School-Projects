@extends('layouts.app')

@section('title', $event->title())

@section('content')
<section class="page-head">
    <div class="wrap">
        <a href="{{ route('sports.show', $event->sport) }}" class="crumb">{{ $event->sport->icon }} {{ $event->sport->name }}</a>
        <h1>{{ $event->name }} <span class="tag tag-{{ $event->gender->value }}">{{ $event->genderLabel() }}</span>
            @if ($event->team)<span class="tag tag-team">Par équipes</span>@endif
            @if ($event->isCancelled())<span class="tag tag-cancelled">Annulée</span>@endif
        </h1>
        <p>📅 {{ $event->starts_at->translatedFormat('l j F Y à H\hi') }} &nbsp;·&nbsp; 📍 {{ $event->venue->name }}, {{ $event->venue->city }}</p>
    </div>
</section>

<section class="wrap grid-2">
    <div class="card">
        <h2>Résultats</h2>
        @if ($event->isCancelled())
            <p class="muted">Cette épreuve a été annulée le {{ $event->cancelled_at->translatedFormat('j F Y') }}.</p>
        @elseif ($podium->isEmpty())
            <p class="muted">
                {{ $event->isPast() ? 'Les résultats seront publiés prochainement.' : "L'épreuve n'a pas encore eu lieu." }}
            </p>
        @else
            <div class="podium">
                @foreach (\App\Enums\Medal::podiumOrder() as $medal)
                    <div class="podium-step podium-{{ $medal->value }}">
                        {{-- Deux bronzes possibles dans les sports de combat : ils partagent la 3e marche. --}}
                        @foreach ($podium->get($medal->value, []) as $result)
                            <div class="podium-winner">
                                {{ $result->country->flag('podium-flag') }}
                                <div class="podium-name">{{ $result->winnerName() }}</div>
                                <a href="{{ route('countries.show', $result->country) }}" class="podium-country">{{ $result->athlete ? $result->country->name : 'Équipe' }}</a>
                            </div>
                        @endforeach
                        <div class="podium-block">@include('partials.medal', ['medal' => $medal])</div>
                    </div>
                @endforeach
            </div>
        @endif

        @if (auth()->user()?->isAdmin())
            <div class="admin-links">
                @if ($event->isPast() && ! $event->isCancelled())
                    <a href="{{ route('admin.events.results', $event) }}" class="btn btn-sm btn-primary">Saisir les résultats</a>
                @endif
                <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-sm btn-ghost-dark">Modifier l'épreuve</a>
            </div>
        @endif
    </div>

    <aside class="card ticket-box">
        <h2>Billetterie</h2>
        <div class="ticket-price">@euros($event->price) <small>/ place</small></div>

        @if ($event->isCancelled())
            <p class="status status-cancelled">Épreuve annulée</p>
            <p class="muted small">Tous les billets ont été remboursés.</p>
        @elseif ($event->isPast())
            <p class="muted">Cette épreuve est terminée.</p>
        @elseif ($seatsLeft === 0)
            <p class="status status-full">Complet</p>
        @else
            <p class="muted">@number($seatsLeft) places disponibles sur @number($event->capacity)</p>
            <div class="progress"><div style="width: {{ round(100 * ($event->capacity - $seatsLeft) / $event->capacity) }}%"></div></div>

            @auth
                @if ($alreadyBooked)
                    <p class="small">🎟️ Vous avez déjà {{ $alreadyBooked }} place(s) pour cette épreuve. <a href="{{ route('tickets.index') }}">Mes billets</a></p>
                @endif
                @if ($maxBookable > 0)
                    <form method="POST" action="{{ route('tickets.store', $event) }}" class="form-inline">
                        @csrf
                        <x-field name="quantity" label="Nombre de places">
                            <select name="quantity">
                                @for ($i = 1; $i <= $maxBookable; $i++)
                                    <option value="{{ $i }}">{{ $i }} — @euros($i * $event->price)</option>
                                @endfor
                            </select>
                        </x-field>
                        <button class="btn btn-accent btn-block">Réserver</button>
                    </form>
                @else
                    <p class="muted small">Limite de {{ \App\Http\Controllers\TicketController::MAX_PER_USER }} places par personne atteinte.</p>
                @endif
            @else
                <a href="{{ route('login', ['epreuve' => $event->id]) }}" class="btn btn-accent btn-block">Se connecter pour réserver</a>
            @endauth
        @endif
    </aside>
</section>
@endsection
