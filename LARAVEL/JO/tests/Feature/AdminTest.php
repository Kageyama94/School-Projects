<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Models\Athlete;
use App\Models\Country;
use App\Models\Event;
use App\Models\Result;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use App\Support\Format;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Espace d'administration : épreuves, résultats, athlètes, sports, sites et pays. */
class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin());
    }

    private function eventData(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Finale test', 'sport_id' => Sport::first()->id, 'venue_id' => Venue::first()->id,
            'gender' => 'F', 'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'price' => 45, 'capacity' => 1000,
        ];
    }

    public function test_admin_area_is_restricted(): void
    {
        $this->post('/deconnexion');
        $this->get(route('admin.events.index'))->assertRedirect(route('login'));

        $this->actingAs($this->spectator());
        foreach (['events', 'athletes', 'sports', 'venues', 'countries', 'users'] as $section) {
            $this->get(route("admin.$section.index"))->assertForbidden();
        }
    }

    public static function adminPages(): array
    {
        return [['admin.events'], ['admin.athletes'], ['admin.sports'], ['admin.venues'], ['admin.countries']];
    }

    #[DataProvider('adminPages')]
    public function test_admin_pages_render(string $section): void
    {
        $model = [
            'admin.events' => Event::class, 'admin.athletes' => Athlete::class, 'admin.sports' => Sport::class,
            'admin.venues' => Venue::class, 'admin.countries' => Country::class,
        ][$section]::first();

        $this->get(route("$section.index"))->assertOk();
        $this->get(route("$section.create"))->assertOk();
        $this->get(route("$section.edit", $model))->assertOk();
    }

    // ---------------------------------------------------------------- Épreuves

    public function test_admin_can_create_update_and_delete_event(): void
    {
        $this->post(route('admin.events.store'), $this->eventData(['team' => '1']))->assertRedirect(route('admin.events.index'));
        $event = Event::where('name', 'Finale test')->firstOrFail();
        $this->assertTrue($event->team);

        $this->put(route('admin.events.update', $event), $this->eventData(['price' => 60]))->assertSessionHasNoErrors();
        $this->assertSame(60, $event->fresh()->price);
        $this->assertFalse($event->fresh()->team);

        $this->delete(route('admin.events.destroy', $event));
        $this->assertModelMissing($event);
    }

    public function test_event_capacity_is_bounded_by_venue_and_tickets_sold(): void
    {
        $venue = Venue::first();
        $this->post(route('admin.events.store'), $this->eventData(['venue_id' => $venue->id, 'capacity' => $venue->capacity + 1]))
            ->assertSessionHasErrors('capacity');

        $event = Event::upcoming()->first();
        $event->tickets()->create(['user_id' => User::first()->id, 'quantity' => 5, 'unit_price' => $event->price]);
        $sold = (int) $event->tickets()->sum('quantity');
        $this->put(route('admin.events.update', $event), $this->eventData([
            'sport_id' => $event->sport_id, 'venue_id' => $event->venue_id, 'gender' => $event->gender->value,
            'starts_at' => $event->starts_at->format('Y-m-d\TH:i'), 'capacity' => $sold - 1,
        ]))->assertSessionHasErrors(['capacity' => Format::number($sold).' billets sont déjà vendus : la capacité ne peut pas être inférieure.']);
    }

    public function test_event_with_tickets_cannot_be_deleted(): void
    {
        $event = Event::has('tickets')->first();

        $this->delete(route('admin.events.destroy', $event))->assertSessionHasErrors('event');
        $this->assertModelExists($event);
    }

    public function test_event_with_results_keeps_sport_category_type_and_stays_in_the_past(): void
    {
        $event = $this->pastIndividualEvent(gender: Gender::Women);
        $base = [
            'sport_id' => $event->sport_id, 'venue_id' => $event->venue_id, 'gender' => $event->gender->value,
            'starts_at' => $event->starts_at->format('Y-m-d\TH:i'), 'capacity' => $event->capacity,
        ];

        $this->put(route('admin.events.update', $event), $this->eventData(['gender' => 'H'] + $base))->assertSessionHasErrors('sport_id');
        $this->put(route('admin.events.update', $event), $this->eventData(['team' => '1'] + $base))->assertSessionHasErrors('sport_id');
        $this->put(route('admin.events.update', $event), $this->eventData(['starts_at' => now()->addDay()->format('Y-m-d\TH:i')] + $base))
            ->assertSessionHasErrors('starts_at');
        $this->put(route('admin.events.update', $event), $this->eventData(['name' => 'Nouveau nom'] + $base))->assertSessionHasNoErrors();
    }

    // ---------------------------------------------------------------- Résultats

    public function test_admin_can_record_individual_results(): void
    {
        $event = $this->pastIndividualEvent(gender: Gender::Women);
        $athletes = Athlete::where('sport_id', $event->sport_id)->where('gender', 'F')->take(3)->get();

        $this->get(route('admin.events.results', $event))->assertOk();
        $this->put(route('admin.events.results.update', $event), [
            'gold' => $athletes[0]->id, 'silver' => $athletes[1]->id, 'bronze' => $athletes[2]->id,
        ])->assertRedirect(route('events.show', $event));

        $gold = $event->results()->where('medal', 'gold')->first();
        $this->assertSame([$athletes[0]->id, $athletes[0]->country_id], [$gold->athlete_id, $gold->country_id]);
    }

    public function test_admin_can_record_team_results(): void
    {
        $event = Event::where('starts_at', '<', now())->where('team', true)->firstOrFail();
        $countries = Country::whereHas('athletes', fn ($q) => $q->where('sport_id', $event->sport_id))->take(3)->pluck('id');

        $this->get(route('admin.events.results', $event))->assertOk();
        $this->put(route('admin.events.results.update', $event), [
            'gold' => $countries[0], 'silver' => $countries[1], 'bronze' => $countries[2],
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, $event->results()->whereNull('athlete_id')->count());
        $this->assertSame($countries[0], $event->results()->where('medal', 'gold')->value('country_id'));
    }

    public function test_results_reject_duplicate_or_ineligible_athletes(): void
    {
        $event = $this->pastIndividualEvent(gender: Gender::Women);
        $woman = Athlete::where('sport_id', $event->sport_id)->where('gender', 'F')->value('id');
        $man = Athlete::where('sport_id', $event->sport_id)->where('gender', 'H')->value('id');

        $this->put(route('admin.events.results.update', $event), ['gold' => $woman, 'silver' => $woman])->assertSessionHasErrors('gold');
        $this->put(route('admin.events.results.update', $event), ['gold' => $man])->assertSessionHasErrors('gold');
    }

    public function test_results_cannot_be_entered_before_the_event(): void
    {
        $event = Event::upcoming()->where('team', false)->first();
        $athlete = Athlete::where('sport_id', $event->sport_id)->value('id');

        $this->get(route('admin.events.results', $event))->assertRedirect(route('admin.events.index'));
        $this->put(route('admin.events.results.update', $event), ['gold' => $athlete])->assertSessionHasErrors('gold');
        $this->assertSame(0, $event->results()->count());
    }

    // ---------------------------------------------------------------- Sports, sites, pays, athlètes

    public function test_sport_crud_and_delete_protection(): void
    {
        $this->post(route('admin.sports.store'), ['name' => 'Volley-ball', 'icon' => '🏐'])->assertSessionHasNoErrors();
        $sport = Sport::where('name', 'Volley-ball')->firstOrFail();
        $this->post(route('admin.sports.store'), ['name' => 'Volley-ball', 'icon' => '🏐'])->assertSessionHasErrors('name');

        $this->put(route('admin.sports.update', $sport), ['name' => 'Volley', 'icon' => '🏐'])->assertSessionHasNoErrors();
        $this->delete(route('admin.sports.destroy', $sport));
        $this->assertModelMissing($sport);

        $used = Sport::has('events')->first();
        $this->delete(route('admin.sports.destroy', $used))->assertSessionHasErrors('sport');
        $this->assertModelExists($used);
    }

    public function test_venue_capacity_cannot_drop_below_its_events(): void
    {
        $venue = Venue::has('events')->first();
        $biggest = (int) $venue->events()->max('capacity');

        $this->put(route('admin.venues.update', $venue), ['name' => $venue->name, 'city' => $venue->city, 'capacity' => $biggest - 1])
            ->assertSessionHasErrors('capacity');
        $this->delete(route('admin.venues.destroy', $venue))->assertSessionHasErrors('venue');

        $this->post(route('admin.venues.store'), ['name' => 'Piscine', 'city' => 'Lyon', 'capacity' => 2000])->assertSessionHasNoErrors();
        $new = Venue::where('name', 'Piscine')->firstOrFail();
        $this->delete(route('admin.venues.destroy', $new));
        $this->assertModelMissing($new);
    }

    public function test_venue_capacity_message_matches_the_situation(): void
    {
        $empty = Venue::create(['name' => 'Vide', 'city' => 'Lyon', 'capacity' => 100]);
        $this->put(route('admin.venues.update', $empty), ['name' => 'Vide', 'city' => 'Lyon', 'capacity' => 0])
            ->assertSessionHasErrors(['capacity' => "Le champ nombre de places doit être d'au moins 1."]);

        $used = Venue::has('events')->first();
        $biggest = (int) $used->events()->max('capacity');
        $this->put(route('admin.venues.update', $used), ['name' => $used->name, 'city' => $used->city, 'capacity' => 1])
            ->assertSessionHasErrors(['capacity' => 'Une épreuve de ce site prévoit '.Format::number($biggest).' places : la capacité ne peut pas être inférieure.']);
    }

    public function test_country_crud_validates_codes(): void
    {
        $this->post(route('admin.countries.store'), ['name' => 'Kenya', 'code' => 'ken', 'iso' => 'ke'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('countries', ['code' => 'KEN', 'iso' => 'ke']);

        $this->post(route('admin.countries.store'), ['name' => 'Autre', 'code' => 'FRA', 'iso' => 'fr'])->assertSessionHasErrors('code');
        $this->post(route('admin.countries.store'), ['name' => 'Autre', 'code' => 'XYZ', 'iso' => 'zz'])->assertSessionHasErrors('iso');

        $kenya = Country::where('code', 'KEN')->first();
        $this->get(route('countries.show', $kenya))->assertOk();
        $this->delete(route('admin.countries.destroy', $kenya));
        $this->assertModelMissing($kenya);

        $this->delete(route('admin.countries.destroy', Country::where('code', 'FRA')->first()))->assertSessionHasErrors('country');
    }

    public function test_country_code_with_accents_is_rejected(): void
    {
        $this->post(route('admin.countries.store'), ['name' => 'Test', 'code' => 'été', 'iso' => 'ke'])
            ->assertSessionHasErrors(['code' => 'Le code CIO ne doit contenir que des lettres sans accent (ex. FRA).']);

        $this->assertDatabaseMissing('countries', ['name' => 'Test']);
    }

    public function test_athlete_crud_search_and_medalist_protection(): void
    {
        $data = ['first_name' => 'Teddy', 'last_name' => 'Testeur', 'gender' => 'H', 'sport_id' => Sport::first()->id, 'country_id' => Country::first()->id];
        $this->post(route('admin.athletes.store'), $data)->assertSessionHasNoErrors();

        $this->get(route('admin.athletes.index', ['q' => 'Testeur']))->assertOk()->assertSee('TESTEUR')->assertViewHas('athletes', fn ($p) => $p->total() === 1);

        $medalist = Result::whereNotNull('athlete_id')->first()->athlete;
        $this->put(route('admin.athletes.update', $medalist), [
            'first_name' => $medalist->first_name, 'last_name' => $medalist->last_name, 'gender' => $medalist->gender->value,
            'sport_id' => $medalist->sport_id, 'country_id' => Country::whereKeyNot($medalist->country_id)->value('id'),
        ])->assertSessionHasErrors('sport_id');
        $this->delete(route('admin.athletes.destroy', $medalist))->assertSessionHasErrors('athlete');
        $this->assertModelExists($medalist);

        $teddy = Athlete::where('last_name', 'Testeur')->first();
        $this->delete(route('admin.athletes.destroy', $teddy));
        $this->assertModelMissing($teddy);
    }
}
