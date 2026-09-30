<?php

namespace Tests\Feature;

use App\Models\Athlete;
use App\Models\Country;
use App\Models\Event;
use App\Models\Result;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Podiums : ordre des médailles, deux bronzes, pages pays et drapeaux. */
class PodiumTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin());
    }

    private function athletes(Event $event, int $count)
    {
        return Athlete::eligibleFor($event)->take($count)->pluck('id');
    }

    public function test_medals_must_be_given_in_order(): void
    {
        $event = $this->pastIndividualEvent('Athlétisme');
        $ids = $this->athletes($event, 2);

        $this->put(route('admin.events.results.update', $event), ['bronze' => $ids[0]])
            ->assertSessionHasErrors(['gold' => "Attribuez la médaille d'or avant les suivantes."]);
        $this->put(route('admin.events.results.update', $event), ['gold' => $ids[0], 'bronze' => $ids[1]])
            ->assertSessionHasErrors(['silver' => "Attribuez la médaille d'argent avant le bronze."]);
    }

    public function test_combat_sports_award_two_bronzes(): void
    {
        $event = $this->pastIndividualEvent('Judo');
        $ids = $this->athletes($event, 4);
        $this->assertTrue($event->sport->two_bronzes);

        $this->get(route('admin.events.results', $event))->assertOk()->assertSee('Médaille de bronze (2)');
        $this->put(route('admin.events.results.update', $event), [
            'gold' => $ids[0], 'silver' => $ids[1], 'bronze' => $ids[2], 'bronze_2' => $ids[3],
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $event->results()->where('medal', 'bronze')->count());
        $page = $this->get(route('events.show', $event));
        foreach (Athlete::whereIn('id', $ids->slice(2))->get() as $bronze) {
            $page->assertSee($bronze->fullName());
        }
    }

    public function test_second_bronze_is_refused_in_other_sports(): void
    {
        $event = $this->pastIndividualEvent('Athlétisme');
        $ids = $this->athletes($event, 4);

        $this->put(route('admin.events.results.update', $event), [
            'gold' => $ids[0], 'silver' => $ids[1], 'bronze' => $ids[2], 'bronze_2' => $ids[3],
        ])->assertSessionHasErrors('bronze_2');
    }

    public function test_two_bronzes_option_cannot_be_removed_once_used(): void
    {
        $judo = Sport::where('name', 'Judo')->first();
        $this->assertTrue(Result::where('medal', 'bronze')->whereHas('event', fn ($q) => $q->where('sport_id', $judo->id))
            ->groupBy('event_id')->havingRaw('COUNT(*) > 1')->exists(), 'le seed contient une épreuve de judo à deux bronzes');

        $this->put(route('admin.sports.update', $judo), ['name' => 'Judo', 'icon' => '🥋'])->assertSessionHasErrors('two_bronzes');
        $this->put(route('admin.sports.update', $judo), ['name' => 'Judo', 'icon' => '🥋', 'two_bronzes' => '1'])->assertSessionHasNoErrors();
    }

    public function test_medal_table_counts_both_bronzes(): void
    {
        $table = Country::medalTable()->get();

        $this->assertSame(Result::where('medal', 'bronze')->count(), (int) $table->sum('bronze'));
        $this->assertGreaterThan(Result::where('medal', 'gold')->count(), Result::where('medal', 'bronze')->count());
    }

    public function test_country_url_is_case_insensitive(): void
    {
        $this->get('/pays/fra')->assertOk()->assertSee('France');
        $this->get('/pays/Fra')->assertOk();
    }

    public function test_flags_next_to_a_name_are_decorative(): void
    {
        $country = Country::where('code', 'FRA')->first();

        $this->assertStringContainsString('alt=""', (string) $country->flag());
        $this->assertStringContainsString('alt="France"', (string) $country->flag('flag', decorative: false));
        $this->get(route('medals.index'))->assertDontSee('alt="France"', false);
    }
}
