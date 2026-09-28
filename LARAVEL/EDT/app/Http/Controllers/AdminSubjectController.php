<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminSubjectController extends Controller
{
    public function create(): View
    {
        return view('admin.subjects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:subjects'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        Subject::create($validated);

        return redirect()->route('admin.teachers.index')->with('success', 'Matière créée.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        return $this->deleteUnlessInUse(
            $subject,
            $subject->lessons()->exists(),
            'admin.teachers.index',
            'Cette matière est utilisée par des cours : impossible de la supprimer.',
            'Matière supprimée.',
        );
    }
}
