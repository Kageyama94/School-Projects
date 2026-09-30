<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Support\Format;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Prix figé des billets et annulation d'épreuves (remboursement). */
class CancellationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_ticket_keeps_the_price_paid(): void
    {
        // Compte neuf : sa page ne contient que ce billet, sans total parasite venant du seed.
        $buyer = User::factory()->create();
        $event = $this->upcomingEventWithoutTickets();
        $event->update(['price' => 30]);
        $this->actingAs($buyer)->post(route('tickets.store', $event), ['quantity' => 3]);

        $event->update(['price' => 130]);

        $ticket = $buyer->tickets()->first();
        $this->assertSame(30, $ticket->unit_price);
        $this->assertSame(90, $ticket->total());
        $this->actingAs($buyer)->get(route('tickets.index'))->assertSee(Format::euros(90))->assertDontSee(Format::euros(390));
    }

    public function test_admin_can_cancel_an_upcoming_event(): void
    {
        $event = Event::upcoming()->has('tickets')->first();

        $this->actingAs($this->admin())->post(route('admin.events.cancel', $event))
            ->assertSessionHas('success', fn ($msg) => str_contains($msg, 'remboursé'));

        $event->refresh();
        $this->assertTrue($event->isCancelled());
        $this->assertFalse(Event::upcoming()->whereKey($event->id)->exists(), "une épreuve annulée n'est plus « à venir »");
        $this->get(route('events.show', $event))->assertSee('Épreuve annulée');
        $this->get(route('events.index', ['status' => 'cancelled']))->assertSee($event->name);
    }

    public function test_cancelled_event_refunds_and_blocks_bookings(): void
    {
        $spectator = $this->spectator();
        $ticket = $spectator->tickets()->whereHas('event', fn ($q) => $q->whereNull('cancelled_at'))->with('event')->first();
        $this->actingAs($this->admin())->post(route('admin.events.cancel', $ticket->event));

        $this->actingAs($spectator)->get(route('tickets.index'))->assertSee('Remboursés')->assertSee(Format::euros($ticket->total()).' remboursés');
        $this->actingAs($spectator)->delete(route('tickets.destroy', $ticket))->assertSessionHasErrors('ticket');
        $this->actingAs($spectator)->post(route('tickets.store', $ticket->event), ['quantity' => 1])
            ->assertSessionHasErrors(['quantity' => 'Cette épreuve a été annulée.']);
    }

    public function test_only_upcoming_events_can_be_cancelled(): void
    {
        $past = Event::where('starts_at', '<', now())->whereNull('cancelled_at')->first();

        $this->actingAs($this->admin())->post(route('admin.events.cancel', $past))->assertSessionHasErrors('event');
        $this->assertNull($past->fresh()->cancelled_at);
    }

    public function test_spectator_cancellation_is_kept_and_counted_as_refunded(): void
    {
        $spectator = $this->spectator();
        $ticket = $spectator->tickets()->active()->whereHas('event', fn ($q) => $q->upcoming())->first();
        $refunded = fn () => $this->actingAs($this->admin())->get(route('admin.dashboard'))->viewData('refunded');
        $before = $refunded();

        $this->actingAs($spectator)->delete(route('tickets.destroy', $ticket))->assertSessionHas('success');

        $this->assertNotNull($ticket->fresh()?->cancelled_at, 'le billet reste en base, marqué annulé');
        $this->assertSame($before + $ticket->quantity, $refunded());
        $this->actingAs($spectator)->get(route('tickets.index'))->assertSee('Annulé par vous');
        $this->actingAs($spectator)->delete(route('tickets.destroy', $ticket))->assertSessionHasErrors('ticket');
    }

    public function test_venues_page_flags_cancelled_events(): void
    {
        $event = Event::whereNotNull('cancelled_at')->first();

        $html = $this->get(route('venues.index'))->getContent();
        $pos = strpos($html, e($event->name).' ('.$event->genderLabel().')');
        $this->assertNotFalse($pos);
        $this->assertStringContainsString('Annulée', substr($html, $pos, 300));
    }

    public function test_delete_button_is_hidden_for_events_with_tickets(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.events.index'))->getContent();

        $this->assertStringNotContainsString(route('admin.events.destroy', Event::has('tickets')->first()).'"', $html);
        $this->assertStringContainsString(route('admin.events.destroy', Event::doesntHave('tickets')->first()).'"', $html);
    }

    public function test_cancelled_event_capacity_ignores_refunded_tickets(): void
    {
        $event = Event::whereNotNull('cancelled_at')->has('tickets')->first();

        $this->actingAs($this->admin())->put(route('admin.events.update', $event), [
            'name' => $event->name, 'sport_id' => $event->sport_id, 'venue_id' => $event->venue_id, 'gender' => $event->gender->value,
            'starts_at' => $event->starts_at->format('Y-m-d\TH:i'), 'price' => $event->price, 'capacity' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, $event->fresh()->capacity);
    }

    public function test_cancelled_event_has_no_results_and_cannot_be_deleted_with_tickets(): void
    {
        $event = Event::whereNotNull('cancelled_at')->firstOrFail(); // annulée par le seed, avec des billets

        $this->actingAs($this->admin())->get(route('admin.events.results', $event))->assertRedirect(route('admin.events.index'));
        $this->actingAs($this->admin())->delete(route('admin.events.destroy', $event))->assertSessionHasErrors('event');
    }
}
