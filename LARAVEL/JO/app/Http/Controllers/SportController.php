<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use Illuminate\View\View;

class SportController extends Controller
{
    public function index(): View
    {
        return view('sports.index', [
            'sports' => Sport::withCount('events', 'athletes')->orderBy('name')->get(),
        ]);
    }

    public function show(Sport $sport): View
    {
        return view('sports.show', [
            'sport' => $sport,
            'events' => $sport->events()->with('venue')->orderBy('starts_at')->get()
                ->each->setRelation('sport', $sport),
            'athletes' => $sport->athletes()->with('country')->orderBy('last_name')->get(),
        ]);
    }
}
