<?php

namespace App\Http\Controllers;

use App\Models\Licence;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminLicenceController extends Controller
{
    public function index(): View
    {
        return view('admin.licences.index', [
            'licences' => Licence::withCount(['groups', 'students'])
                ->with(['groups' => fn ($query) => $query->withCount(['students', 'lessons'])->orderBy('level')->orderBy('name')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.licences.form', ['licence' => new Licence]);
    }

    public function store(Request $request): RedirectResponse
    {
        Licence::create($request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:licences'],
        ]));

        return redirect()->route('admin.licences.index')->with('success', 'Licence créée.');
    }

    public function edit(Licence $licence): View
    {
        return view('admin.licences.form', ['licence' => $licence]);
    }

    public function update(Request $request, Licence $licence): RedirectResponse
    {
        $licence->update($request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('licences')->ignore($licence)],
        ]));

        return redirect()->route('admin.licences.index')->with('success', 'Licence mise à jour.');
    }

    public function destroy(Licence $licence): RedirectResponse
    {
        return $this->deleteUnlessInUse(
            $licence,
            $licence->groups()->exists(),
            'admin.licences.index',
            'Cette licence a des groupes : supprime ou déplace ses groupes avant de la supprimer.',
            'Licence supprimée.',
        );
    }
}
