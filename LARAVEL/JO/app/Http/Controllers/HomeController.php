<?php

namespace App\Http\Controllers;

use App\Enums\Medal;
use App\Models\Athlete;
use App\Models\Country;
use App\Models\Event;
use App\Models\Result;
use App\Models\Sport;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Les derniers champions sont les plus récents d'après la date de l'épreuve, pas l'ordre de saisie. */
    public function index(): View
    {
        return view('home', [
            'upcoming' => Event::upcoming()->with('sport', 'venue')->take(6)->get(),
            'podium' => Country::medalTable()->take(3)->get(),
            'latestResults' => Result::with('event.sport', 'athlete', 'country')
                ->where('medal', Medal::Gold)
                ->orderByDesc(Event::select('starts_at')->whereColumn('events.id', 'results.event_id'))
                ->take(5)->get(),
            'stats' => [
                'sports' => Sport::count(),
                'events' => Event::count(),
                'athletes' => Athlete::count(),
                'countries' => Country::count(),
            ],
        ]);
    }
}
