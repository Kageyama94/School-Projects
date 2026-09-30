<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TicketController extends Controller
{
    /** Nombre maximum de places par spectateur et par épreuve, toutes commandes confondues. */
    public const MAX_PER_USER = 6;

    public function index(Request $request): View
    {
        $tickets = $request->user()->tickets()
            ->with('event.sport', 'event.venue')
            ->get()
            ->sortBy('event.starts_at');

        return view('tickets.index', [
            'upcoming' => $tickets->filter(fn (Ticket $ticket) => ! $ticket->isRefunded() && ! $ticket->event->isPast()),
            'refunded' => $tickets->filter(fn (Ticket $ticket) => $ticket->isRefunded()),
            'past' => $tickets->filter(fn (Ticket $ticket) => ! $ticket->isRefunded() && $ticket->event->isPast()),
        ]);
    }

    /**
     * Transaction exclusive (lockForUpdate sous MySQL, mode IMMEDIATE sous SQLite) : toutes les
     * vérifications se font sur l'état relu dans la transaction, pour que ni une autre réservation
     * ni une annulation de l'épreuve au même moment ne puissent passer entre la vérification et l'écriture.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:'.self::MAX_PER_USER,
        ]);

        $error = DB::transaction(function () use ($event, $data, $request): ?string {
            $event = Event::lockForUpdate()->find($event->id);

            if ($event->isCancelled()) {
                return 'Cette épreuve a été annulée.';
            }
            if ($event->isPast()) {
                return 'Cette épreuve est déjà passée.';
            }

            $already = (int) $request->user()->tickets()->active()->where('event_id', $event->id)->sum('quantity');
            if ($already + $data['quantity'] > self::MAX_PER_USER) {
                return 'Maximum '.self::MAX_PER_USER." places par personne pour une épreuve (vous en avez déjà $already).";
            }
            if ($event->seatsLeft() < $data['quantity']) {
                return 'Il ne reste pas assez de places.';
            }

            $request->user()->tickets()->create([
                'event_id' => $event->id,
                'quantity' => $data['quantity'],
                'unit_price' => $event->price,
            ]);

            return null;
        });

        if ($error) {
            return back()->withErrors(['quantity' => $error]);
        }

        return redirect()->route('tickets.index')->with('success', 'Réservation confirmée !');
    }

    /** Annulation par le spectateur : le billet est marqué annulé (et remboursé), pas effacé. */
    public function destroy(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);

        if ($ticket->isRefunded()) {
            return back()->withErrors(['ticket' => 'Ce billet a déjà été remboursé.']);
        }
        if ($ticket->event->isPast()) {
            return back()->withErrors(['ticket' => "Impossible d'annuler un billet pour une épreuve passée."]);
        }

        $ticket->forceFill(['cancelled_at' => now()])->save();

        return back()->with('success', 'Billet annulé, vous serez remboursé de '.Format::euros($ticket->total()).'.');
    }
}
