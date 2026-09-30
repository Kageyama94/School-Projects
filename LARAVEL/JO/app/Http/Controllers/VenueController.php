<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(): View
    {
        return view('venues.index', [
            'venues' => Venue::with(['events' => fn (HasMany $query) => $query->with('sport')->orderBy('starts_at')])
                ->orderBy('name')->get(),
        ]);
    }
}
