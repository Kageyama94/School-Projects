<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Event;
use App\Models\Result;
use App\Models\Sport;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Pages publiques et billetterie. Le lazy loading est interdit en test : une requête N+1 fait échouer la page. */
class OlympicsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public static function publicPages(): array
    {
        return [['/'], ['/sports'], ['/sports/1'], ['/epreuves'], ['/epreuves?status=upcoming'], ['/medailles'], ['/pays/FRA'], ['/sites'], ['/connexion'], ['/inscription'], ['/mot-de-passe-oublie']];
    }

    #[DataProvider('publicPages')]
    public function test_public_pages_render(string $url): void
    {
        $this->get($url)->assertOk();
    }

    public function test_every_event_and_country_page_renders(): void
    {
        foreach (Event::all() as $event) {
            $this->get(route('events.show', $event))->assertOk();
        }
        foreach (Country::all() as $country) {
            $this->get(route('countries.show', $country))->assertOk();
        }
        $this->get(route('events.show', Event::where('starts_at', '<', now())->first()))->assertSee('Cette épreuve est terminée');
        $this->get(route('events.show', $this->upcomingEventWithoutTickets()))->assertSee('Se connecter pour réserver');
    }

    public function test_times_are_in_paris_timezone(): void
    {
        $this->assertSame('Europe/Paris', config('app.timezone'));

        Carbon::setTestNow(Carbon::parse('2027-07-01 13:00', 'Europe/Paris'));
        $event = Event::first();
        $event->update(['starts_at' => '2027-07-01 14:00']);
        $this->assertFalse($event->fresh()->isPast());

        Carbon::setTestNow(Carbon::parse('2027-07-01 14:30', 'Europe/Paris'));
        $this->assertTrue($event->fresh()->isPast());
        $this->get(route('events.show', $event))->assertSee('à 14h00');
    }

    public function test_medal_table_is_sorted_and_counts_team_medals(): void
    {
        $table = Country::medalTable()->get();
        $rows = $table->map(fn ($c) => [$c->gold, $c->silver, $c->bronze])->all();
        $sorted = $rows;
        rsort($sorted);

        $this->assertSame($sorted, $rows);
        $this->assertSame(Result::count(), (int) $table->sum('total'));
        $this->assertTrue(Result::whereNull('athlete_id')->exists(), 'le seed contient des médailles par équipes');
    }

    public function test_team_event_podium_shows_countries(): void
    {
        $result = Result::whereNull('athlete_id')->with('country', 'event')->first();

        $this->get(route('events.show', $result->event))->assertOk()->assertSee('Par équipes')->assertSee($result->country->name);
    }

    public function test_latest_champions_are_ordered_by_event_date(): void
    {
        $dates = $this->get('/')->viewData('latestResults')->map(fn ($r) => $r->event->starts_at->timestamp)->all();
        $sorted = $dates;
        rsort($sorted);

        $this->assertSame($sorted, $dates);
        $this->assertSame(
            Event::has('results')->max('starts_at'),
            $this->get('/')->viewData('latestResults')->first()->event->starts_at->format('Y-m-d H:i:s'),
        );
    }

    public function test_booking_requires_login(): void
    {
        $this->post(route('tickets.store', $this->upcomingEventWithoutTickets()), ['quantity' => 2])->assertRedirect(route('login'));
    }

    public function test_login_from_event_page_returns_to_the_event(): void
    {
        $event = $this->upcomingEventWithoutTickets();

        $this->get(route('login', ['epreuve' => $event->id]))->assertOk();
        $this->post('/connexion', ['email' => 'spectateur@jo.test', 'password' => 'password'])
            ->assertRedirect(route('events.show', $event));
    }

    public function test_spectator_can_book_and_cancel(): void
    {
        $event = $this->upcomingEventWithoutTickets();
        $before = $event->seatsLeft();

        $this->actingAs($this->spectator())
            ->post(route('tickets.store', $event), ['quantity' => 3])
            ->assertRedirect(route('tickets.index'));
        $this->assertSame($before - 3, $event->seatsLeft());

        $ticket = Ticket::latest('id')->first();
        $this->actingAs($this->spectator())->delete(route('tickets.destroy', $ticket))->assertRedirect();
        $this->assertNotNull($ticket->fresh()->cancelled_at, 'le billet annulé reste en base pour l\'historique');
        $this->assertSame($before, $event->seatsLeft(), 'les places sont libérées');
    }

    public function test_six_seats_max_per_person_across_orders(): void
    {
        $event = $this->upcomingEventWithoutTickets();
        $this->actingAs($this->spectator())->post(route('tickets.store', $event), ['quantity' => 4]);

        $this->actingAs($this->spectator())
            ->post(route('tickets.store', $event), ['quantity' => 3])
            ->assertSessionHasErrors(['quantity' => 'Maximum 6 places par personne pour une épreuve (vous en avez déjà 4).']);

        $this->actingAs($this->spectator())->post(route('tickets.store', $event), ['quantity' => 2])->assertSessionHasNoErrors();
        $this->actingAs($this->spectator())->get(route('events.show', $event))->assertSee('Limite de 6 places par personne atteinte');
    }

    public function test_cannot_overbook(): void
    {
        $event = $this->upcomingEventWithoutTickets();
        $event->update(['capacity' => 2]);

        $this->actingAs($this->spectator())
            ->post(route('tickets.store', $event), ['quantity' => 3])
            ->assertSessionHasErrors(['quantity' => 'Il ne reste pas assez de places.']);
    }

    public function test_cannot_book_past_event(): void
    {
        $event = Event::where('starts_at', '<', now())->first();

        $this->actingAs($this->spectator())
            ->post(route('tickets.store', $event), ['quantity' => 1])
            ->assertSessionHasErrors('quantity');
    }

    public function test_cannot_cancel_someone_elses_ticket(): void
    {
        $ticket = $this->spectator()->tickets()->first();
        $other = User::factory()->create();

        $this->actingAs($other)->delete(route('tickets.destroy', $ticket))->assertForbidden();
    }

    public function test_flags_are_served_locally(): void
    {
        $country = Country::where('code', 'FRA')->first();

        $this->assertStringContainsString(asset('img/flags/fr.png'), (string) $country->flag());
        $this->assertFileExists(public_path('img/flags/fr.png'));
        foreach (array_keys(Country::worldList()) as $iso) {
            $this->assertFileExists(public_path("img/flags/$iso.png"));
        }
    }

    public function test_favicon_is_a_real_icon_and_is_linked(): void
    {
        $ico = file_get_contents(public_path('favicon.ico'));
        $this->assertSame("\x00\x00\x01\x00", substr($ico, 0, 4), 'en-tête ICO valide');
        $this->assertFileExists(public_path('favicon.svg'));

        $this->get('/')->assertSee('favicon.svg')->assertSee('favicon.ico');
        $this->get('/page-inexistante')->assertSee('favicon.svg');
    }

    public function test_sport_page_lists_its_events(): void
    {
        $sport = Sport::has('events')->first();

        $this->get(route('sports.show', $sport))->assertOk()->assertSee($sport->events()->first()->name);
    }
}
