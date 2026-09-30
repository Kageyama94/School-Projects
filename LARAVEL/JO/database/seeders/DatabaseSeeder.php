<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Models\Athlete;
use App\Models\Country;
use App\Models\Event;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration : les pays sont réels, les athlètes et résultats sont fictifs.
 * Les épreuves sont réparties autour de la date du seed pour avoir à la fois
 * des épreuves terminées (avec médailles) et des épreuves à venir (réservables).
 */
class DatabaseSeeder extends Seeder
{
    private const COUNTRIES = [
        // code CIO => [nom, code ISO, locale Faker]
        'FRA' => ['France', 'fr', 'fr_FR'],
        'USA' => ['États-Unis', 'us', 'en_US'],
        'CHN' => ['Chine', 'cn', 'zh_CN'],
        'JPN' => ['Japon', 'jp', 'ja_JP'],
        'GBR' => ['Grande-Bretagne', 'gb', 'en_GB'],
        'AUS' => ['Australie', 'au', 'en_AU'],
        'ITA' => ['Italie', 'it', 'it_IT'],
        'GER' => ['Allemagne', 'de', 'de_DE'],
        'NED' => ['Pays-Bas', 'nl', 'nl_NL'],
        'BRA' => ['Brésil', 'br', 'pt_BR'],
        'ESP' => ['Espagne', 'es', 'es_ES'],
        'CAN' => ['Canada', 'ca', 'en_CA'],
        'KOR' => ['Corée du Sud', 'kr', 'ko_KR'],
        'SWE' => ['Suède', 'se', 'sv_SE'],
        'NOR' => ['Norvège', 'no', 'nb_NO'],
    ];

    private const VENUES = [
        // clé => [nom, ville, capacité]
        'stade' => ['Stade Olympique', 'Saint-Denis', 77000],
        'aqua' => ['Centre Aquatique Olympique', 'Saint-Denis', 5000],
        'arena' => ['Arena Olympique', 'Paris', 15000],
        'velo' => ['Vélodrome National', 'Saint-Quentin-en-Yvelines', 5000],
        'palais' => ['Palais des Sports', 'Paris', 8000],
        'nautique' => ['Stade Nautique', 'Vaires-sur-Marne', 14000],
        'court' => ['Court Central', 'Paris', 15000],
        'esplanade' => ['Esplanade des Invalides', 'Paris', 8000],
        'surf' => ['Spot de Teahupo\'o', 'Tahiti', 1000],
    ];

    private const SPORTS = [
        // nom => [icône, description, site, [[épreuve, genre, par équipes ?], ...], deux bronzes ?]
        'Athlétisme' => ['🏃', 'Courses, sauts et lancers : le sport roi des Jeux.', 'stade', [
            ['100 m', Gender::Men], ['100 m', Gender::Women], ['Marathon', Gender::Women], ['Saut en longueur', Gender::Men],
        ]],
        'Natation' => ['🏊', 'Des bassins où se jouent les records au centième près.', 'aqua', [
            ['100 m nage libre', Gender::Women], ['200 m papillon', Gender::Men], ['Relais 4 × 100 m 4 nages', Gender::Mixed, true],
        ]],
        'Judo' => ['🥋', 'Art martial japonais, discipline phare de la délégation française.', 'arena', [
            ['-73 kg', Gender::Men], ['-57 kg', Gender::Women], ['Par équipes', Gender::Mixed, true],
        ], true],
        'Cyclisme sur piste' => ['🚴', 'Vitesse et tactique sur l\'anneau du vélodrome.', 'velo', [
            ['Vitesse individuelle', Gender::Women], ['Keirin', Gender::Men],
        ]],
        'Escrime' => ['🤺', 'Fleuret, épée et sabre : l\'élégance du duel.', 'palais', [
            ['Fleuret individuel', Gender::Women], ['Épée individuelle', Gender::Men],
        ]],
        'Basketball' => ['🏀', 'Tournois masculin et féminin jusqu\'aux finales.', 'arena', [
            ['Finale du tournoi', Gender::Men, true], ['Finale du tournoi', Gender::Women, true],
        ]],
        'Gymnastique' => ['🤸', 'Force, souplesse et précision aux agrès.', 'palais', [
            ['Concours général', Gender::Women], ['Barre fixe', Gender::Men],
        ]],
        'Tennis' => ['🎾', 'Le tournoi olympique sur terre battue.', 'court', [
            ['Simple dames', Gender::Women], ['Simple messieurs', Gender::Men],
        ]],
        'Aviron' => ['🚣', 'Endurance et synchronisation sur 2 000 mètres.', 'nautique', [
            ['Skiff', Gender::Men], ['Deux de couple', Gender::Women],
        ]],
        'Tir à l\'arc' => ['🏹', 'Concentration absolue à 70 mètres de la cible.', 'esplanade', [
            ['Individuel', Gender::Women], ['Par équipes', Gender::Mixed, true],
        ]],
        'Boxe' => ['🥊', 'Le noble art sur le ring olympique.', 'arena', [
            ['-57 kg', Gender::Women], ['-80 kg', Gender::Men],
        ], true],
        'Surf' => ['🏄', 'Les meilleures vagues du monde pour les surfeurs olympiques.', 'surf', [
            ['Shortboard', Gender::Women], ['Shortboard', Gender::Men],
        ]],
    ];

    private const ATHLETES_PER_GENDER = 8;

    /**
     * Les épreuves s'étalent de J-8 à J+10 par rapport au seed : avec cet ordre, il y a des épreuves
     * par équipes terminées (relais, tir à l'arc) et à venir (judo, basket). Camille a des billets pour
     * trois épreuves à venir ; la dernière est annulée (billets remboursés).
     */
    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Administrateur', 'email' => 'admin@jo.test']);
        $spectator = User::factory()->create(['name' => 'Camille Spectatrice', 'email' => 'spectateur@jo.test']);

        $countries = collect(self::COUNTRIES)->map(fn (array $country, string $code) => Country::create([
            'code' => $code, 'name' => $country[0], 'iso' => $country[1],
        ]));

        $fakers = collect(self::COUNTRIES)->map(fn (array $country) => Faker::create($country[2]));

        $venues = collect(self::VENUES)->map(fn (array $venue) => Venue::create([
            'name' => $venue[0], 'city' => $venue[1], 'capacity' => $venue[2],
        ]));

        $day = -8;
        foreach (self::SPORTS as $name => $def) {
            [$icon, $description, $venueKey, $events] = $def;
            $sport = Sport::create(['name' => $name, 'icon' => $icon, 'description' => $description, 'two_bronzes' => $def[4] ?? false]);

            foreach (Gender::forAthletes() as $gender) {
                foreach ($countries->keys()->shuffle()->take(self::ATHLETES_PER_GENDER) as $code) {
                    $faker = $fakers[$code];
                    Athlete::create([
                        'first_name' => $faker->firstName($gender === Gender::Women ? 'female' : 'male'),
                        'last_name' => $faker->lastName(),
                        'gender' => $gender,
                        'country_id' => $countries[$code]->id,
                        'sport_id' => $sport->id,
                    ]);
                }
            }

            foreach ($events as $eventDefinition) {
                [$eventName, $gender] = $eventDefinition;
                $venue = $venues[$venueKey];
                $event = Event::create([
                    'sport_id' => $sport->id,
                    'venue_id' => $venue->id,
                    'name' => $eventName,
                    'gender' => $gender,
                    'team' => $eventDefinition[2] ?? false,
                    'starts_at' => now()->startOfDay()->addDays($day)->setTime(mt_rand(9, 21), [0, 30][mt_rand(0, 1)]),
                    'price' => [30, 50, 75, 90, 120, 180][mt_rand(0, 5)],
                    'capacity' => $venue->capacity,
                ])->setRelation('sport', $sport);
                $day = $day >= 10 ? -8 : $day + 1;

                if ($event->isPast()) {
                    $this->awardMedals($event);
                }
            }
        }

        $booked = Event::upcoming()->take(3)->get();
        foreach ($booked as $event) {
            $spectator->tickets()->create(['event_id' => $event->id, 'quantity' => mt_rand(1, 4), 'unit_price' => $event->price]);
        }
        $booked->last()->forceFill(['cancelled_at' => now()])->save();
    }

    /** Podium complet (deux bronzes en judo et en boxe). Par équipes, la médaille revient au pays et non à un athlète. */
    private function awardMedals(Event $event): void
    {
        $medals = array_values($event->medalSlots());

        $podium = Athlete::eligibleFor($event)
            ->get()
            ->shuffle()
            ->unique('country_id')
            ->take(count($medals))
            ->values();

        foreach ($medals as $i => $medal) {
            $event->results()->create([
                'medal' => $medal,
                'country_id' => $podium[$i]->country_id,
                'athlete_id' => $event->team ? null : $podium[$i]->id,
            ]);
        }
    }
}
