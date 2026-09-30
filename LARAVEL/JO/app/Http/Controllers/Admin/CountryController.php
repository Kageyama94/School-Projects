<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CountryRequest;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function index(): View
    {
        return view('admin.countries.index', [
            'countries' => Country::withCount('athletes', 'results')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.countries.form', ['country' => new Country, 'world' => Country::worldList()]);
    }

    public function store(CountryRequest $request): RedirectResponse
    {
        Country::create($request->validated());

        return redirect()->route('admin.countries.index')->with('success', 'Pays ajouté.');
    }

    public function edit(Country $country): View
    {
        return view('admin.countries.form', ['country' => $country, 'world' => Country::worldList()]);
    }

    public function update(CountryRequest $request, Country $country): RedirectResponse
    {
        $country->update($request->validated());

        return redirect()->route('admin.countries.index')->with('success', 'Pays mis à jour.');
    }

    public function destroy(Country $country): RedirectResponse
    {
        if ($country->athletes()->exists() || $country->results()->exists()) {
            return back()->withErrors(['country' => "Impossible de supprimer « {$country->name} » : des athlètes ou des médailles y sont rattachés."]);
        }

        $country->delete();

        return redirect()->route('admin.countries.index')->with('success', 'Pays supprimé.');
    }
}
