<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Medal;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Http\Requests\EventResultsRequest;
use App\Models\Athlete;
use App\Models\Country;
use App\Models\Event;
use App\Models\Sport;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        return view('admin.events.index', [
            'events' => Event::with('sport', 'venue')
                ->withCount('results')
                ->withSum(['tickets' => fn (Builder $query) => $query->active()], 'quantity')
                ->withCount('tickets') // toutes les lignes, y compris annulées : elles bloquent la suppression
                ->orderBy('starts_at')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.events.form', $this->formData(new Event(['starts_at' => now()->addWeek()->setTime(10, 0)])));
    }

    public function store(EventRequest $request): RedirectResponse
    {
        Event::create($request->validated());

        return redirect()->route('admin.events.index')->with('success', 'Épreuve créée.');
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', $this->formData($event));
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->validated());

        return redirect()->route('admin.events.index')->with('success', 'Épreuve mise à jour.');
    }

    /** Des billets, même annulés, font partie de l'historique des ventes : l'épreuve n'est alors pas supprimée. */
    public function destroy(Event $event): RedirectResponse
    {
        if ($event->tickets()->exists()) {
            $hint = $event->isCancelled() || $event->isPast() ? '' : ' Annulez-la plutôt : les spectateurs seront remboursés.';

            return back()->withErrors(['event' => "Impossible de supprimer « {$event->name} » : des billets ont été vendus.$hint"]);
        }

        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Épreuve supprimée.');
    }

    /** Annulation définitive d'une épreuve à venir : les billets sont remboursés. cancelled_at est hors $fillable. */
    public function cancel(Event $event): RedirectResponse
    {
        if ($event->isCancelled() || $event->isPast()) {
            return back()->withErrors(['event' => 'Seule une épreuve à venir peut être annulée.']);
        }

        $event->forceFill(['cancelled_at' => now()])->save();
        $refunded = $event->seatsSold();

        return back()->with('success', "« {$event->name} » est annulée.".($refunded ? " $refunded billet(s) remboursé(s)." : ''));
    }

    public function editResults(Event $event): View|RedirectResponse
    {
        if ($reason = $event->resultsLockedReason()) {
            return redirect()->route('admin.events.index')->withErrors(['event' => $reason]);
        }

        $event->load('sport', 'results');
        $winners = $event->results->groupBy('medal')->map->pluck($event->team ? 'country_id' : 'athlete_id');

        return view('admin.events.results', [
            'event' => $event,
            'slots' => $event->medalSlots(),
            'candidates' => $event->team
                ? Country::eligibleFor($event)->orderBy('name')->get()
                : Athlete::eligibleFor($event)->with('country')->orderBy('last_name')->get(),
            'current' => [
                'gold' => $winners[Medal::Gold->value][0] ?? null,
                'silver' => $winners[Medal::Silver->value][0] ?? null,
                'bronze' => $winners[Medal::Bronze->value][0] ?? null,
                'bronze_2' => $winners[Medal::Bronze->value][1] ?? null,
            ],
        ]);
    }

    /** Le podium validé (voir EventResultsRequest) remplace les résultats précédents. */
    public function updateResults(EventResultsRequest $request, Event $event): RedirectResponse
    {
        $slots = $event->medalSlots();
        $picked = array_filter($request->validated());
        $athleteCountries = $event->team ? [] : Athlete::whereIn('id', $picked)->pluck('country_id', 'id');

        DB::transaction(function () use ($event, $slots, $picked, $athleteCountries): void {
            $event->results()->delete();
            foreach ($picked as $slot => $id) {
                $event->results()->create([
                    'medal' => $slots[$slot],
                    'athlete_id' => $event->team ? null : $id,
                    'country_id' => $event->team ? $id : $athleteCountries[$id],
                ]);
            }
        });

        return redirect()->route('events.show', $event)->with('success', 'Résultats enregistrés.');
    }

    /**
     * @return array{event: Event, sports: Collection<int, Sport>, venues: Collection<int, Venue>}
     */
    private function formData(Event $event): array
    {
        return [
            'event' => $event,
            'sports' => Sport::orderBy('name')->get(),
            'venues' => Venue::orderBy('name')->get(),
        ];
    }
}
