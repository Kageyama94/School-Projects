<?php

namespace App\Http\Controllers;

use App\Actions\CreateAccount;
use App\Actions\UpdateProfile;
use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminStudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        return view('admin.students.index', [
            'students' => Student::with(['user', 'group.licence'])
                ->when($search !== '', fn (Builder $query) => $query->whereHas(
                    'user',
                    fn (Builder $user) => $user->where('identifiant', 'like', $search.'%')
                ))
                ->orderBy('last_name')
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.students.create', ['groups' => $this->groupsByLicence()]);
    }

    public function store(Request $request, CreateAccount $createAccount): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'group_id' => ['required', 'exists:groups,id'],
        ]);

        $credentials = $createAccount->handle(
            UserRole::Student,
            $validated,
            fn (User $user) => Student::create([
                'user_id' => $user->id,
                'group_id' => $validated['group_id'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
            ]),
        );

        return redirect()->route('admin.students.index')->with('credentials', ['message' => 'Étudiant créé.', ...$credentials]);
    }

    public function edit(Student $student): View
    {
        return view('admin.students.edit', [
            'student' => $student->load('user'),
            'groups' => $this->groupsByLicence(),
        ]);
    }

    public function update(Request $request, Student $student, UpdateProfile $updateProfile): RedirectResponse
    {
        $validated = $request->validate([
            ...UpdateProfile::rules($student),
            'group_id' => ['nullable', 'exists:groups,id'],
        ]);

        DB::transaction(fn () => $updateProfile->handle($student, $validated));

        return redirect()->route('admin.students.index')->with('success', 'Étudiant mis à jour.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->deleteWithAccount($student);

        return redirect()->route('admin.students.index')->with('success', 'Étudiant supprimé, ainsi que son compte.');
    }

    /**
     * Les groupes classés par licence, niveau puis nom, pour les listes de choix.
     *
     * @return Collection<string, Collection<int, Group>>
     */
    private function groupsByLicence(): Collection
    {
        return Group::with('licence')->ordered()->get()->groupBy('licence.name');
    }
}
