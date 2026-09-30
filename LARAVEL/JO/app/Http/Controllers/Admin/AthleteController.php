<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AthleteRequest;
use App\Models\Athlete;
use App\Models\Country;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AthleteController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'sport' => 'nullable|integer',
            'country' => 'nullable|integer',
        ]);

        $athletes = Athlete::with('sport', 'country')
            ->withCount('results')
            ->when($filters['q'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('last_name', 'like', "%$search%")
                ->orWhere('first_name', 'like', "%$search%")))
            ->when($filters['sport'] ?? null, fn (Builder $query, int|string $id) => $query->where('sport_id', $id))
            ->when($filters['country'] ?? null, fn (Builder $query, int|string $id) => $query->where('country_id', $id))
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.athletes.index', [
            'athletes' => $athletes,
            'filters' => $filters,
            ...$this->options(),
        ]);
    }

    public function create(): View
    {
        return view('admin.athletes.form', ['athlete' => new Athlete, ...$this->options()]);
    }

    public function store(AthleteRequest $request): RedirectResponse
    {
        Athlete::create($request->validated());

        return redirect()->route('admin.athletes.index')->with('success', 'Athlète ajouté.');
    }

    public function edit(Athlete $athlete): View
    {
        return view('admin.athletes.form', ['athlete' => $athlete, ...$this->options()]);
    }

    public function update(AthleteRequest $request, Athlete $athlete): RedirectResponse
    {
        $athlete->update($request->validated());

        return redirect()->route('admin.athletes.index')->with('success', 'Athlète mis à jour.');
    }

    public function destroy(Athlete $athlete): RedirectResponse
    {
        if ($athlete->results()->exists()) {
            return back()->withErrors(['athlete' => "Impossible de supprimer {$athlete->fullName()} : cet athlète a des médailles."]);
        }

        $athlete->delete();

        return back()->with('success', 'Athlète supprimé.');
    }

    /**
     * @return array{sports: Collection<int, Sport>, countries: Collection<int, Country>}
     */
    private function options(): array
    {
        return [
            'sports' => Sport::orderBy('name')->get(),
            'countries' => Country::orderBy('name')->get(),
        ];
    }
}
