<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Seuil à partir duquel une épreuve est signalée « presque complète ». */
    public const NEARLY_FULL = 0.9;

    /**
     * Billets valides : ni annulés par le spectateur, ni sur une épreuve annulée.
     * Remboursés : annulés par le spectateur ou par l'organisation (épreuve annulée).
     */
    public function __invoke(): View
    {
        $eventHeld = fn (Builder $query) => $query->whereNull('cancelled_at');
        $eventCancelled = fn (Builder $query) => $query->whereNotNull('cancelled_at');

        $upcoming = Event::upcoming()->with('sport')
            ->withSum(['tickets' => fn (Builder $query) => $query->active()], 'quantity')
            ->get()
            ->each(fn (Event $event) => $event->fill_rate = $event->capacity ? (int) $event->tickets_sum_quantity / $event->capacity : 0)
            ->sortByDesc('fill_rate');

        return view('admin.dashboard', [
            'revenue' => (int) Ticket::active()->whereHas('event', $eventHeld)->sum(DB::raw('quantity * unit_price')),
            'sold' => (int) Ticket::active()->whereHas('event', $eventHeld)->sum('quantity'),
            'refunded' => (int) Ticket::where(fn (Builder $query) => $query->whereNotNull('cancelled_at')->orWhereHas('event', $eventCancelled))->sum('quantity'),
            'spectators' => User::where('role', Role::Spectator)->count(),
            'fillRate' => $upcoming->sum('capacity') ? $upcoming->sum('tickets_sum_quantity') / $upcoming->sum('capacity') : 0,
            'upcoming' => $upcoming,
            'awaitingResults' => Event::with('sport')
                ->where('starts_at', '<', now())->whereNull('cancelled_at')->doesntHave('results')
                ->orderBy('starts_at')->get(),
            'topSales' => Event::with('sport')->whereNull('cancelled_at')
                ->select('events.*')
                ->selectSub(Ticket::selectRaw('COALESCE(SUM(quantity * unit_price), 0)')->active()->whereColumn('tickets.event_id', 'events.id'), 'revenue')
                ->orderByDesc('revenue')->take(5)->get()
                ->filter(fn (Event $event) => $event->revenue > 0),
        ]);
    }
}
