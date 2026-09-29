<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_teacher_and_student_gets_an_account_in_a_random_order(): void
    {
        $this->seed();

        $this->assertSame(0, Teacher::whereNull('user_id')->count());
        $this->assertSame(0, Student::whereNull('user_id')->count());

        // Comptes de démonstration inchangés.
        $this->assertTrue(Hash::check('prof', User::where('identifiant', '2')->firstOrFail()->password));
        $this->assertFalse(User::where('identifiant', '3')->firstOrFail()->must_change_password);

        // Les autres : suite continue à partir de 94020000, mot de passe initial à changer.
        $generated = User::where('identifiant', '>=', (string) User::FIRST_REAL_IDENTIFIANT)->orderBy('identifiant')->get();
        $expected = range(User::FIRST_REAL_IDENTIFIANT, User::FIRST_REAL_IDENTIFIANT + Teacher::count() - 1 + Student::count() - 2);
        $this->assertSame(array_map('strval', $expected), $generated->pluck('identifiant')->all());
        $this->assertTrue($generated->every->must_change_password);

        // Enseignants et étudiants sont mélangés : la suite n'est pas « tous les enseignants, puis tous les étudiants ».
        $roles = $generated->pluck('role');
        $this->assertNotSame($roles->sortBy(fn (UserRole $role) => $role === UserRole::Teacher ? 0 : 1)->values()->all(), $roles->all());

        $teacher = Teacher::whereRelation('user', 'identifiant', '>=', (string) User::FIRST_REAL_IDENTIFIANT)->firstOrFail();
        $this->assertTrue(Hash::check(User::defaultPassword($teacher->first_name, $teacher->last_name), $teacher->user->password));
        $this->assertSame($teacher->full_name, $teacher->user->name);
    }
}
