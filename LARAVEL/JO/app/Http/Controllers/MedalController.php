<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Result;
use Illuminate\View\View;

class MedalController extends Controller
{
    public function index(): View
    {
        return view('medals.index', [
            'table' => Country::medalTable()->get(),
        ]);
    }

    public function country(Country $country): View
    {
        return view('medals.country', [
            'country' => $country,
            'athletes' => $country->athletes()->with('sport')->orderBy('last_name')->get(),
            'medals' => $country->results()
                ->with('event.sport', 'athlete')
                ->get()
                ->each->setRelation('country', $country)
                ->sortBy(fn (Result $result) => $result->medal->rank())
                ->values(),
        ]);
    }
}
