<?php

namespace App\Http\Controllers;

use App\Actions\CreateAccount;
use App\Actions\TransferLessons;
use App\Actions\UpdateProfile;
use App\Enums\UserRole;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminTeacherController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        return view('admin.teachers.index', [
            'teachers' => Teacher::with(['user', 'subjects'])->withCount('lessons')
                ->when($search !== '', fn (Builder $query) => $query->matchingName($search))
                ->orderBy('last_name')
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
            'subjects' => Subject::withCount('lessons')->orderBy('name')
                ->paginate(self::PER_PAGE, pageName: 'subjects_page')
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.teachers.create');
    }

    public function store(Request $request, CreateAccount $createAccount): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ]);

        $credentials = $createAccount->handle(
            UserRole::Teacher,
            $validated,
            fn (User $user) => Teacher::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
            ]),
        );

        return redirect()->route('admin.teachers.index')->with('credentials', ['message' => 'Enseignant créé.', ...$credentials]);
    }

    public function show(Teacher $teacher): View
    {
        return view('admin.timetable', [
            'title' => "Emploi du temps — {$teacher->full_name}",
            'back' => route('admin.teachers.index'),
            'lessons' => $teacher->lessons()->with(['subject', 'group.licence', 'room'])->orderBy('start_time')->get(),
        ]);
    }

    public function edit(Teacher $teacher): View
    {
        $lessonSubjects = Subject::withCount(['lessons' => fn ($query) => $query->where('teacher_id', $teacher->id)])
            ->whereHas('lessons', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->orderBy('name')
            ->get();

        return view('admin.teachers.edit', [
            'teacher' => $teacher->load(['user', 'subjects']),
            'subjects' => Subject::orderBy('name')->get(),
            'lessonSubjects' => $lessonSubjects,
            // Chargés seulement si le formulaire « Confier ses cours » va s'afficher (l'enseignant a des cours).
            'replacements' => $lessonSubjects->isEmpty()
                ? collect()
                : Teacher::with('subjects')->whereKeyNot($teacher->id)->orderBy('last_name')->get(),
        ]);
    }

    public function update(Request $request, Teacher $teacher, UpdateProfile $updateProfile): RedirectResponse
    {
        $validated = $request->validate([
            ...UpdateProfile::rules($teacher),
            'subjects' => ['sometimes', 'array'],
            'subjects.*' => ['exists:subjects,id'],
        ]);
        $subjects = $validated['subjects'] ?? [];

        $this->ensureRemovedSubjectsHaveNoLessons($teacher, $subjects);

        DB::transaction(function () use ($updateProfile, $teacher, $validated, $subjects) {
            $updateProfile->handle($teacher, $validated);
            $teacher->subjects()->sync($subjects);
        });

        return redirect()->route('admin.teachers.index')->with('success', 'Enseignant mis à jour.');
    }

    public function transferLessons(Request $request, Teacher $teacher, TransferLessons $transferLessons): RedirectResponse
    {
        $validated = $request->validate([
            'replacement_id' => ['required', 'exists:teachers,id', Rule::notIn([$teacher->id])],
            'subject_id' => ['nullable', 'exists:subjects,id'],
        ]);

        $replacement = Teacher::findOrFail($validated['replacement_id']);
        $count = $transferLessons->handle($teacher, $replacement, $validated['subject_id'] ?? null);

        return redirect()->route('admin.teachers.edit', $teacher)
            ->with('success', "{$count} cours confié(s) à {$replacement->full_name}.");
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        $this->deleteWithAccount($teacher);

        return redirect()->route('admin.teachers.index')->with('success', 'Enseignant supprimé, ainsi que son compte et ses cours.');
    }

    /**
     * Une matière ne peut être retirée que si l'enseignant n'y a plus de cours (il faut d'abord les confier à un autre).
     *
     * @param  array<int, int|string>  $newSubjectIds
     *
     * @throws ValidationException
     */
    private function ensureRemovedSubjectsHaveNoLessons(Teacher $teacher, array $newSubjectIds): void
    {
        $removedIds = $teacher->subjects()->pluck('subjects.id')->diff($newSubjectIds);

        $inUse = Subject::whereIn('id', $removedIds)
            ->withCount(['lessons' => fn ($query) => $query->where('teacher_id', $teacher->id)])
            ->get()
            ->filter(fn (Subject $subject) => $subject->lessons_count > 0);

        if ($inUse->isNotEmpty()) {
            $list = $inUse->map(fn (Subject $subject) => "{$subject->name} ({$subject->lessons_count} cours)")->join(', ');

            throw ValidationException::withMessages([
                'subjects' => "Impossible de retirer : {$list}. Confie d'abord ces cours à un autre enseignant (formulaire ci-dessous).",
            ]);
        }
    }
}
