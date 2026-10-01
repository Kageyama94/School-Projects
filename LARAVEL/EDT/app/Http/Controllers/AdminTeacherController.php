<?php

namespace App\Http\Controllers;

use App\Actions\CreateAccount;
use App\Actions\TransferLessons;
use App\Actions\UpdateProfile;
use App\Enums\UserRole;
use App\Models\Licence;
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
        $search = $this->searchTerm($request);

        return view('admin.teachers.index', [
            'teachers' => Teacher::with(['user', 'subjects', 'licences'])->withCount('lessons')
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
        return view('admin.teachers.create', [
            'subjects' => Subject::orderBy('name')->get(),
            'licences' => Licence::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CreateAccount $createAccount): RedirectResponse
    {
        $validated = $request->validate([
            ...UpdateProfile::rules(),
            ...self::assignmentRules(),
        ]);

        $credentials = $createAccount->handle(
            UserRole::Teacher,
            $validated,
            function (User $user) use ($validated) {
                $teacher = Teacher::create([
                    'user_id' => $user->id,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                ]);
                $teacher->subjects()->sync($validated['subjects'] ?? []);
                $teacher->licences()->sync($validated['licences'] ?? []);
            },
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

    public function edit(Teacher $teacher, TransferLessons $transferLessons): View
    {
        $lessonSubjects = Subject::withCount(['lessons' => fn ($query) => $query->where('teacher_id', $teacher->id)])
            ->whereHas('lessons', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->orderBy('name')
            ->get();

        return view('admin.teachers.edit', [
            'teacher' => $teacher->load(['user', 'subjects', 'licences']),
            'subjects' => Subject::orderBy('name')->get(),
            'licences' => Licence::orderBy('name')->get(),
            'lessonSubjects' => $lessonSubjects,
            // Chargés seulement si le formulaire « Confier ses cours » va s'afficher (l'enseignant a des cours).
            'replacements' => $lessonSubjects->isEmpty() ? collect() : $transferLessons->candidates($teacher),
        ]);
    }

    /**
     * Matières et licences cochées sur la fiche d'un enseignant.
     *
     * @return array<string, array<mixed>>
     */
    private static function assignmentRules(): array
    {
        return [
            'subjects' => ['sometimes', 'array'],
            'subjects.*' => ['exists:subjects,id'],
            'licences' => ['sometimes', 'array'],
            'licences.*' => ['exists:licences,id'],
        ];
    }

    public function update(Request $request, Teacher $teacher, UpdateProfile $updateProfile): RedirectResponse
    {
        $validated = $request->validate([
            ...UpdateProfile::rules(),
            ...self::assignmentRules(),
        ]);
        $subjects = $validated['subjects'] ?? [];
        $licences = $validated['licences'] ?? [];

        $this->ensureRemovedHaveNoLessons($teacher, 'subjects', $subjects);
        $this->ensureRemovedHaveNoLessons($teacher, 'licences', $licences);

        DB::transaction(function () use ($updateProfile, $teacher, $validated, $subjects, $licences) {
            $updateProfile->handle($teacher, $validated);
            $teacher->subjects()->sync($subjects);
            $teacher->licences()->sync($licences);
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
     * Une matière ou une licence ne peut être retirée que si l'enseignant n'y a plus de cours
     * (il faut d'abord les confier à un autre).
     *
     * @param  'subjects'|'licences'  $relation
     * @param  array<int, int|string>  $keptIds
     *
     * @throws ValidationException
     */
    private function ensureRemovedHaveNoLessons(Teacher $teacher, string $relation, array $keptIds): void
    {
        $assigned = $teacher->{$relation}();
        $removedIds = $assigned->pluck($assigned->getRelated()->getQualifiedKeyName())->diff($keptIds);

        $inUse = $assigned->getRelated()->newQuery()
            ->whereKey($removedIds)
            ->withCount(['lessons' => fn ($query) => $query->where('teacher_id', $teacher->id)])
            ->get()
            ->filter(fn ($model) => $model->lessons_count > 0);

        if ($inUse->isNotEmpty()) {
            $list = $inUse->map(fn ($model) => "{$model->name} ({$model->lessons_count} cours)")->join(', ');

            throw ValidationException::withMessages([
                $relation => "Impossible de retirer : {$list}. Confie d'abord ces cours à un autre enseignant (formulaire ci-dessous).",
            ]);
        }
    }
}
