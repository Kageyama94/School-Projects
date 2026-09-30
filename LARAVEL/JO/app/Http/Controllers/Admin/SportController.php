<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SportRequest;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SportController extends Controller
{
    public function index(): View
    {
        return view('admin.sports.index', [
            'sports' => Sport::withCount('events', 'athletes')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.sports.form', ['sport' => new Sport]);
    }

    public function store(SportRequest $request): RedirectResponse
    {
        Sport::create($request->validated());

        return redirect()->route('admin.sports.index')->with('success', 'Sport créé.');
    }

    public function edit(Sport $sport): View
    {
        return view('admin.sports.form', ['sport' => $sport]);
    }

    public function update(SportRequest $request, Sport $sport): RedirectResponse
    {
        $sport->update($request->validated());

        return redirect()->route('admin.sports.index')->with('success', 'Sport mis à jour.');
    }

    public function destroy(Sport $sport): RedirectResponse
    {
        if ($sport->events()->exists() || $sport->athletes()->exists()) {
            return back()->withErrors(['sport' => "Impossible de supprimer « {$sport->name} » : des épreuves ou des athlètes y sont rattachés."]);
        }

        $sport->delete();

        return redirect()->route('admin.sports.index')->with('success', 'Sport supprimé.');
    }
}
