<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'sport' => 'nullable|integer|exists:sports,id',
            'date' => 'nullable|date',
            'status' => 'nullable|in:upcoming,past,cancelled',
        ]);

        $events = Event::with('sport', 'venue')
            ->when($filters['sport'] ?? null, fn (Builder $query, int|string $sport) => $query->where('sport_id', $sport))
            ->when($filters['date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('starts_at', $date))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => match ($status) {
                'upcoming' => $query->upcoming(),
                'past' => $query->where('starts_at', '<=', now())->whereNull('cancelled_at'),
                'cancelled' => $query->whereNotNull('cancelled_at'),
            })
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Event $event) => $event->starts_at->toDateString());

        return view('events.index', [
            'eventsByDay' => $events,
            'sports' => Sport::orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    /** Le podium est groupé par médaille : il peut y avoir deux bronzes (sports de combat). */
    public function show(Request $request, Event $event): View
    {
        $event->load('sport', 'venue', 'results.athlete', 'results.country');

        $seatsLeft = $event->seatsLeft();
        $alreadyBooked = $request->user()
            ? (int) $request->user()->tickets()->active()->where('event_id', $event->id)->sum('quantity')
            : 0;

        return view('events.show', [
            'event' => $event,
            'podium' => $event->results->groupBy('medal'),
            'seatsLeft' => $seatsLeft,
            'alreadyBooked' => $alreadyBooked,
            'maxBookable' => min(TicketController::MAX_PER_USER - $alreadyBooked, $seatsLeft),
        ]);
    }
}
