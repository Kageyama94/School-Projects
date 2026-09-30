<a href="{{ route('events.show', $event) }}" class="event-card">
    <div class="event-icon">{{ $event->sport->icon }}</div>
    <div class="event-body">
        <div class="event-sport">{{ $event->sport->name }}</div>
        <div class="event-name">{{ $event->name }} <span class="tag tag-{{ $event->gender->value }}">{{ $event->genderLabel() }}</span>@if ($event->team) <span class="tag tag-team">Équipes</span>@endif</div>
        <div class="event-meta">
            📅 {{ $event->starts_at->translatedFormat('D j M · H\hi') }}
            &nbsp;·&nbsp; 📍 {{ $event->venue->name }}
        </div>
    </div>
    <div class="event-side">
        @if ($event->isCancelled())
            <span class="status status-cancelled">Annulée</span>
        @elseif ($event->isPast())
            <span class="status status-done">Terminée</span>
        @else
            <span class="price">@euros($event->price)</span>
        @endif
    </div>
</a>
