<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $user = User::factory()->teacher()->create();

        $this->actingAs($user)->get(route('admin.teachers.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.students.index'))->assertForbidden();
    }

    public function test_real_accounts_get_identifiants_starting_at_94020000(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create();

        $this->post(route('admin.teachers.store'), [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);
        $this->post(route('admin.students.store'), [
            'first_name' => 'Marie',
            'last_name' => 'Curie',
            'group_id' => $group->id,
        ]);

        $this->assertSame('94020000', Teacher::where('last_name', 'Dupont')->firstOrFail()->user->identifiant);
        $this->assertSame('94020001', Student::where('last_name', 'Curie')->firstOrFail()->user->identifiant);
    }

    public function test_identifiant_is_limited_to_8_digits(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->student()->create(['identifiant' => '94020006']);
        $student = Student::factory()->create(['user_id' => $user->id]);
        $payload = ['first_name' => 'Marie', 'last_name' => 'Curie', 'group_id' => $student->group_id];

        $this->put(route('admin.students.update', $student), $payload + ['identifiant' => '99999999999999999999'])
            ->assertSessionHasErrors('identifiant');
        $this->put(route('admin.students.update', $student), $payload + ['identifiant' => '123456789'])
            ->assertSessionHasErrors('identifiant');
        $this->put(route('admin.students.update', $student), $payload + ['identifiant' => '99999999'])
            ->assertSessionHasNoErrors();

        $this->assertSame('99999999', $user->fresh()->identifiant);
    }

    public function test_a_very_high_manual_identifiant_does_not_block_the_sequence(): void
    {
        User::factory()->create(['identifiant' => '99999999']);

        $this->assertSame('94020000', User::generateIdentifiant());
    }

    public function test_the_first_free_number_is_used_and_gaps_are_filled(): void
    {
        foreach (['1', '2', '94020000', '94020001', '94020003', '95000000'] as $identifiant) {
            User::factory()->create(['identifiant' => $identifiant]);
        }

        $this->assertSame('94020002', User::generateIdentifiant());

        User::factory()->create(['identifiant' => '94020002']);

        $this->assertSame('94020004', User::generateIdentifiant());
    }

    public function test_no_identifiant_is_generated_when_the_range_is_exhausted(): void
    {
        User::factory()->create(['identifiant' => '10']);
        User::factory()->create(['identifiant' => '11']);

        $this->assertSame('12', User::generateIdentifiant(first: 10, last: 12));

        User::factory()->create(['identifiant' => '12']);

        $this->expectException(RuntimeException::class);

        User::generateIdentifiant(first: 10, last: 12);
    }

    public function test_teachers_and_students_share_one_sequence_starting_at_94020000(): void
    {
        $this->actingAsAdmin();
        User::factory()->create(['identifiant' => '1']);
        User::factory()->create(['identifiant' => '2']);
        $group = Group::factory()->create();

        $this->post(route('admin.students.store'), ['first_name' => 'Marie', 'last_name' => 'Curie', 'group_id' => $group->id]);
        $this->post(route('admin.teachers.store'), ['first_name' => 'Jean', 'last_name' => 'Dupont']);
        $this->post(route('admin.students.store'), ['first_name' => 'Paul', 'last_name' => 'Martin', 'group_id' => $group->id]);

        $identifiants = User::whereIn('name', ['Marie Curie', 'Jean Dupont', 'Paul Martin'])
            ->orderBy('id')->pluck('identifiant')->all();

        $this->assertSame(['94020000', '94020001', '94020002'], $identifiants);
    }

    public function test_identifiant_must_be_numeric_and_unique_when_editing_a_profile(): void
    {
        $this->actingAsAdmin();
        User::factory()->create(['identifiant' => '94020005']);
        $user = User::factory()->student()->create(['identifiant' => '94020006']);
        $student = Student::factory()->create(['user_id' => $user->id]);
        $payload = [
            'first_name' => 'Marie',
            'last_name' => 'Curie',
            'group_id' => $student->group_id,
        ];

        $this->put(route('admin.students.update', $student), $payload + ['identifiant' => '94020005'])
            ->assertSessionHasErrors('identifiant');
        $this->put(route('admin.students.update', $student), $payload + ['identifiant' => 'abc'])
            ->assertSessionHasErrors('identifiant');
        $this->put(route('admin.students.update', $student), $payload + ['identifiant' => '94020006'])
            ->assertSessionHasNoErrors();

        $this->assertSame('94020006', $user->fresh()->identifiant);
    }

    public function test_edit_pages_are_displayed_with_the_profile_fields(): void
    {
        $this->actingAsAdmin();
        $teacherUser = User::factory()->teacher()->create(['identifiant' => '94020010']);
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id, 'first_name' => 'Jean']);
        $studentUser = User::factory()->student()->create(['identifiant' => '94020011']);
        $student = Student::factory()->create(['user_id' => $studentUser->id, 'last_name' => 'Curie']);

        $this->get(route('admin.teachers.edit', $teacher))
            ->assertOk()->assertSee('value="Jean"', false)->assertSee('value="94020010"', false);
        $this->get(route('admin.students.edit', $student))
            ->assertOk()->assertSee('value="Curie"', false)->assertSee('value="94020011"', false);
    }

    public function test_non_admin_cannot_delete_or_edit_people(): void
    {
        $this->actingAs(User::factory()->teacher()->create());
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create();

        $this->delete(route('admin.teachers.destroy', $teacher))->assertForbidden();
        $this->delete(route('admin.students.destroy', $student))->assertForbidden();
        $this->put(route('admin.students.update', $student), [
            'first_name' => 'X',
            'last_name' => 'Y',
            'group_id' => $student->group_id,
        ])->assertForbidden();
        $this->put(route('admin.teachers.update', $teacher), ['first_name' => 'X', 'last_name' => 'Y', 'subjects' => [1]])->assertForbidden();

        $this->assertModelExists($teacher);
        $this->assertModelExists($student);
    }

    public function test_credentials_are_shown_once_after_creating_an_account(): void
    {
        $this->actingAsAdmin();

        $response = $this->followingRedirects()->post(route('admin.teachers.store'), [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);

        $response->assertSee('Enseignant créé.')
            ->assertSee('<strong>94020000</strong>', false)
            ->assertSee('<strong>dupont_jean</strong>', false);

        $this->get(route('admin.teachers.index'))->assertDontSee('dupont_jean');
    }

    public function test_validation_errors_use_french_field_names(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('admin.students.store'), []);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'group_id']);
        $this->assertStringContainsString('prénom', session('errors')->first('first_name'));
        $this->assertStringContainsString('groupe', session('errors')->first('group_id'));
    }

    public function test_admin_can_reset_a_users_password(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->teacher()->create(['must_change_password' => false]);
        $teacher = Teacher::factory()->create(['user_id' => $user->id, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);

        $response = $this->post(route('admin.users.password.reset', $user));

        $response->assertRedirect();
        $user->refresh();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('lovelace_ada', $user->password));
    }

    public function test_password_reset_closes_the_open_sessions_of_that_user(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->student()->create();
        Student::factory()->create(['user_id' => $user->id]);
        $other = User::factory()->student()->create();
        foreach ([[$user->id, 'session-a'], [$user->id, 'session-b'], [$other->id, 'session-c']] as [$userId, $id]) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => '', 'last_activity' => time()]);
        }

        $this->post(route('admin.users.password.reset', $user))->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'session-c']);
    }

    public function test_password_cannot_be_reset_for_an_account_without_a_teacher_or_student_record(): void
    {
        $admin = $this->actingAsAdmin();
        $oldHash = $admin->password;

        $this->post(route('admin.users.password.reset', $admin))->assertNotFound();

        $this->assertSame($oldHash, $admin->fresh()->password);
        $this->assertFalse($admin->fresh()->must_change_password);
    }
}
