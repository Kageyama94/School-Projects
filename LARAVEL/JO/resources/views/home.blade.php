@extends('layouts.app')

@section('content')
<section class="hero">
    <div class="wrap hero-inner">
        <div>
            <p class="hero-kicker">Citius · Altius · Fortius — Communiter</p>
            <h1>Vivez les Jeux<br>Olympiques en direct</h1>
            <p class="hero-text">Calendrier des épreuves, tableau des médailles en temps réel et billetterie officielle : tout pour suivre la compétition.</p>
            <div class="hero-actions">
                <a href="{{ route('events.index', ['status' => 'upcoming']) }}" class="btn btn-accent">Réserver des billets</a>
                <a href="{{ route('medals.index') }}" class="btn btn-outline-light">Tableau des médailles</a>
            </div>
        </div>

        @if ($next = $upcoming->first())
            <div class="countdown-card">
                <div class="countdown-label">Prochaine épreuve</div>
                <div class="countdown-event">{{ $next->fullTitle() }}</div>
                <div class="countdown" data-target="{{ $next->starts_at->toIso8601String() }}">
                    <div><span data-unit="d">–</span>jours</div>
                    <div><span data-unit="h">–</span>heures</div>
                    <div><span data-unit="m">–</span>min</div>
                    <div><span data-unit="s">–</span>sec</div>
                </div>
                <a href="{{ route('events.show', $next) }}" class="countdown-link">Voir l'épreuve →</a>
            </div>
        @endif
    </div>
</section>

<section class="wrap stats">
    <div class="stat"><strong>{{ $stats['sports'] }}</strong><span>sports</span></div>
    <div class="stat"><strong>{{ $stats['events'] }}</strong><span>épreuves</span></div>
    <div class="stat"><strong>{{ $stats['athletes'] }}</strong><span>athlètes</span></div>
    <div class="stat"><strong>{{ $stats['countries'] }}</strong><span>nations</span></div>
</section>

<section class="wrap grid-2">
    <div>
        <div class="section-head">
            <h2>Prochaines épreuves</h2>
            <a href="{{ route('events.index', ['status' => 'upcoming']) }}">Tout le calendrier →</a>
        </div>
        <div class="stack">
            @forelse ($upcoming as $event)
                @include('partials.event-card')
            @empty
                <p class="muted">Aucune épreuve à venir.</p>
            @endforelse
        </div>
    </div>

    <aside class="stack-lg">
        <div class="card">
            <div class="section-head">
                <h2>Podium des nations</h2>
                <a href="{{ route('medals.index') }}">Classement →</a>
            </div>
            @forelse ($podium as $i => $country)
                <a href="{{ route('countries.show', $country) }}" class="podium-row">
                    <span class="rank rank-{{ $i + 1 }}">{{ $i + 1 }}</span>
                    {{ $country->flag() }}
                    <span class="grow">{{ $country->name }}</span>
                    <span class="counts">
                        <span class="dot dot-gold"></span>{{ $country->gold }}
                        <span class="dot dot-silver"></span>{{ $country->silver }}
                        <span class="dot dot-bronze"></span>{{ $country->bronze }}
                    </span>
                </a>
            @empty
                <p class="muted">Aucune médaille décernée pour l'instant.</p>
            @endforelse
        </div>

        <div class="card">
            <h2>Derniers champions</h2>
            @forelse ($latestResults as $result)
                <a href="{{ route('events.show', $result->event) }}" class="champion-row">
                    @include('partials.medal', ['medal' => \App\Enums\Medal::Gold])
                    <div class="grow">
                        <div><strong>{{ $result->winnerName() }}</strong> {{ $result->country->flag('flag-sm', decorative: false) }}</div>
                        <div class="muted small">{{ $result->event->sport->name }} · {{ $result->event->name }} ({{ $result->event->genderLabel() }})</div>
                    </div>
                </a>
            @empty
                <p class="muted">Les premiers titres arrivent bientôt.</p>
            @endforelse
        </div>
    </aside>
</section>
@endsection

@push('scripts')
<script>
    (function () {
        const el = document.querySelector('.countdown');
        if (!el) return;
        const target = new Date(el.dataset.target).getTime();
        const units = { d: 86400, h: 3600, m: 60, s: 1 };
        function tick() {
            let left = Math.floor((target - Date.now()) / 1000);
            if (left <= 0) {
                clearInterval(timer);
                el.outerHTML = '<div class="countdown-live">🏁 L\'épreuve a commencé !</div>';
                return;
            }
            for (const [u, secs] of Object.entries(units)) {
                el.querySelector(`[data-unit="${u}"]`).textContent = String(Math.floor(left / secs)).padStart(2, '0');
                left %= secs;
            }
        }
        const timer = setInterval(tick, 1000);
        tick();
    })();
</script>
@endpush
