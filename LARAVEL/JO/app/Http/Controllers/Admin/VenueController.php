<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VenueRequest;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(): View
    {
        return view('admin.venues.index', [
            'venues' => Venue::withCount('events')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.venues.form', ['venue' => new Venue]);
    }

    public function store(VenueRequest $request): RedirectResponse
    {
        Venue::create($request->validated());

        return redirect()->route('admin.venues.index')->with('success', 'Site créé.');
    }

    public function edit(Venue $venue): View
    {
        return view('admin.venues.form', ['venue' => $venue]);
    }

    public function update(VenueRequest $request, Venue $venue): RedirectResponse
    {
        $venue->update($request->validated());

        return redirect()->route('admin.venues.index')->with('success', 'Site mis à jour.');
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        if ($venue->events()->exists()) {
            return back()->withErrors(['venue' => "Impossible de supprimer « {$venue->name} » : des épreuves s'y déroulent."]);
        }

        $venue->delete();

        return redirect()->route('admin.venues.index')->with('success', 'Site supprimé.');
    }
}
