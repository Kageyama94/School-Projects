<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Format;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tableau de bord de l'administration. */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin());
    }

    public function test_dashboard_shows_revenue_without_cancelled_events(): void
    {
        $expected = Ticket::with('event')->get()->reject(fn ($t) => $t->event->isCancelled())->sum(fn ($t) => $t->total());
        $refunded = (int) Ticket::whereHas('event', fn ($q) => $q->whereNotNull('cancelled_at'))->sum('quantity');
        $this->assertGreaterThan(0, $refunded, 'le seed contient une épreuve annulée avec des billets');

        $this->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('revenue', $expected)
            ->assertViewHas('refunded', $refunded)
            ->assertSee(Format::euros($expected));
    }

    public function test_dashboard_lists_events_awaiting_results(): void
    {
        $event = Event::where('starts_at', '<', now())->whereNull('cancelled_at')->first();
        $event->results()->delete();

        $this->get(route('admin.dashboard'))->assertViewHas('awaitingResults', fn ($list) => $list->contains($event));
    }

    public function test_nearly_full_events_are_flagged_with_a_label(): void
    {
        $event = $this->upcomingEventWithoutTickets();
        $event->update(['capacity' => 10]);
        $event->tickets()->create(['user_id' => User::first()->id, 'quantity' => 6, 'unit_price' => 50]);
        $event->tickets()->create(['user_id' => $this->spectator()->id, 'quantity' => 3, 'unit_price' => 50]);

        $this->get(route('admin.dashboard'))->assertSee('Presque complet')->assertSee(Format::percent(0.9));
    }

    public function test_admins_land_on_the_dashboard(): void
    {
        $this->post('/deconnexion');
        $this->post('/connexion', ['email' => 'admin@jo.test', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
    }
}
