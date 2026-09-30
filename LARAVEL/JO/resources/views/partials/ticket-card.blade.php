{{-- Billet de « Mes billets ». $ticket, et $status : upcoming (annulable), refunded ou past. --}}
<div @class(['ticket', 'ticket-refunded' => $status === 'refunded', 'ticket-past' => $status === 'past'])>
    <div class="ticket-main">
        <div class="event-sport">{{ $ticket->event->sport->icon }} {{ $ticket->event->sport->name }}</div>
        <a href="{{ route('events.show', $ticket->event) }}" class="event-name">{{ $ticket->event->name }} ({{ $ticket->event->genderLabel() }})</a>
        <div class="event-meta">
            @if ($status === 'refunded')
                <span class="status status-cancelled">{{ $ticket->isCancelled() ? 'Annulé par vous' : 'Épreuve annulée' }}</span>
                prévue le {{ $ticket->event->starts_at->translatedFormat('j F') }}
            @elseif ($status === 'past')
                {{ $ticket->event->starts_at->translatedFormat('j F Y') }}
            @else
                📅 {{ $ticket->event->starts_at->translatedFormat('l j F · H\hi') }} · 📍 {{ $ticket->event->venue->name }}
            @endif
        </div>
    </div>
    <div class="ticket-stub">
        <div class="ticket-qty">× {{ $ticket->quantity }}</div>
        <div class="small">@euros($ticket->total()){{ $status === 'refunded' ? ' remboursés' : '' }}</div>
        @if ($status === 'upcoming')
            <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" onsubmit="return confirm('Annuler ce billet ?')">
                @csrf
                @method('DELETE')
                <button class="link-danger">Annuler</button>
            </form>
        @endif
    </div>
</div>
