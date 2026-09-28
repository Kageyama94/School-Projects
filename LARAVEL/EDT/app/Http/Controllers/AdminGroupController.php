<?php

namespace App\Http\Controllers;

use App\Enums\Level;
use App\Models\Group;
use App\Models\Licence;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminGroupController extends Controller
{
    public function create(Request $request): View
    {
        return view('admin.groups.form', $this->formData(new Group(['licence_id' => $request->integer('licence_id') ?: null])));
    }

    public function store(Request $request): RedirectResponse
    {
        Group::create($this->validated($request));

        return redirect()->route('admin.licences.index')->with('success', 'Groupe créé.');
    }

    public function show(Group $group): View
    {
        return view('admin.timetable', [
            'title' => "Emploi du temps — {$group->label}",
            'back' => route('admin.licences.index'),
            'lessons' => $group->lessons()->with(['subject', 'teacher', 'room'])->orderBy('start_time')->get(),
        ]);
    }

    public function edit(Group $group): View
    {
        return view('admin.groups.form', $this->formData($group));
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        $group->update($this->validated($request, $group));

        return redirect()->route('admin.licences.index')->with('success', 'Groupe mis à jour.');
    }

    public function destroy(Group $group): RedirectResponse
    {
        return $this->deleteUnlessInUse(
            $group,
            $group->students()->exists() || $group->lessons()->exists(),
            'admin.licences.index',
            'Ce groupe a des étudiants ou des cours : impossible de le supprimer.',
            'Groupe supprimé.',
        );
    }

    private function formData(Group $group): array
    {
        return [
            'group' => $group,
            'studentsCount' => $group->exists ? $group->students()->count() : 0,
            'licences' => Licence::orderBy('name')->get(),
            'levels' => Level::cases(),
        ];
    }

    /**
     * Le nom d'un groupe est unique au sein d'une même licence et d'un même niveau.
     */
    private function validated(Request $request, ?Group $group = null): array
    {
        return $request->validate([
            'licence_id' => ['required', 'exists:licences,id'],
            'level' => ['required', Rule::enum(Level::class)],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('groups')
                    ->where(fn ($query) => $query
                        ->where('licence_id', $request->input('licence_id'))
                        ->where('level', $request->input('level')))
                    ->ignore($group),
            ],
        ]);
    }
}
