<?php

namespace Tests\Feature;

use App\Enums\Level;
use App\Models\Group;
use App\Models\Licence;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_student(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create();

        $response = $this->post(route('admin.students.store'), [
            'first_name' => 'Marie',
            'last_name' => 'Curie',
            'group_id' => $group->id,
        ]);

        $response->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', [
            'first_name' => 'Marie',
            'last_name' => 'Curie',
            'group_id' => $group->id,
        ]);
    }

    public function test_admin_can_update_a_students_profile_and_move_them_to_a_group_of_another_licence(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->student()->create(['name' => 'Marie Curi']);
        $student = Student::factory()->create(['user_id' => $user->id, 'first_name' => 'Marie', 'last_name' => 'Curi']);
        $newGroup = Group::factory()->create(['level' => Level::L3]);

        $response = $this->put(route('admin.students.update', $student), [
            'first_name' => 'Marie',
            'last_name' => 'Curie',
            'group_id' => $newGroup->id,
        ]);

        $response->assertRedirect(route('admin.students.index'));
        $student->refresh();
        $this->assertSame('Curie', $student->last_name);
        $this->assertSame($newGroup->id, $student->group_id);
        $this->assertSame(Level::L3, $student->group->level);
        $this->assertSame($newGroup->licence_id, $student->group->licence_id);
        $this->assertSame('Marie Curie', $user->fresh()->name);
    }

    public function test_admin_can_remove_a_student_from_their_group(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->student()->create();
        $student = Student::factory()->create(['user_id' => $user->id, 'group_id' => Group::factory()->create()->id]);

        $response = $this->put(route('admin.students.update', $student), [
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'group_id' => '',
        ]);

        $response->assertRedirect(route('admin.students.index'));
        $this->assertNull($student->fresh()->group_id);
    }

    public function test_admin_can_delete_a_student_with_their_account(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->student()->create();
        $student = Student::factory()->create(['user_id' => $user->id]);

        $response = $this->delete(route('admin.students.destroy', $student));

        $response->assertRedirect(route('admin.students.index'));
        $this->assertModelMissing($student);
        $this->assertModelMissing($user);
    }

    public function test_student_form_offers_groups_organised_by_licence_and_level(): void
    {
        $this->actingAsAdmin();
        $info = Licence::factory()->create(['name' => 'Informatique']);
        Group::factory()->create(['licence_id' => $info->id, 'level' => Level::L2, 'name' => 'Groupe A']);

        $this->get(route('admin.students.create'))
            ->assertOk()
            ->assertSee('<optgroup label="Informatique">', false)
            ->assertSee('L2 · Groupe A');
    }

    public function test_student_forms_force_an_explicit_group_choice_on_creation(): void
    {
        $this->actingAsAdmin();
        Group::factory(2)->create();

        $this->get(route('admin.students.create'))
            ->assertOk()
            ->assertSee('— Choisir un groupe —')
            ->assertSee('required', false);

        $this->post(route('admin.students.store'), ['first_name' => 'Marie', 'last_name' => 'Curie', 'group_id' => ''])
            ->assertSessionHasErrors('group_id');
        $this->assertDatabaseCount('students', 0);
    }

    public function test_student_edit_form_allows_removing_the_group(): void
    {
        $this->actingAsAdmin();
        $student = Student::factory()->create(['group_id' => Group::factory()->create()->id]);

        $this->get(route('admin.students.edit', $student))
            ->assertOk()
            ->assertSee('— Aucun groupe —');
    }

    public function test_student_edit_form_preselects_the_current_group(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create(['name' => 'Groupe Cible']);
        Group::factory()->create();
        $student = Student::factory()->create(['group_id' => $group->id]);

        $this->get(route('admin.students.edit', $student))
            ->assertOk()
            ->assertSee('value="'.$group->id.'" selected', false);
    }

    public function test_student_form_asks_to_create_a_group_first_when_none_exists(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.students.create'))->assertOk()->assertSee('Crée d\'abord un', false);
    }

    public function test_student_list_shows_the_licence_level_and_group(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create([
            'licence_id' => Licence::factory()->create(['name' => 'Informatique'])->id,
            'level' => Level::L2,
            'name' => 'Groupe A',
        ]);
        Student::factory()->create(['group_id' => $group->id]);

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Informatique')
            ->assertSee('L2')
            ->assertSee('Groupe A');
    }

    public function test_student_list_is_paginated_by_10(): void
    {
        $this->actingAsAdmin();
        Student::factory(12)->create();

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertViewHas('students', fn ($students) => $students->count() === 10 && $students->total() === 12);

        $this->get(route('admin.students.index', ['page' => 2]))
            ->assertViewHas('students', fn ($students) => $students->count() === 2);
    }

    public function test_students_can_be_searched_by_identifiant(): void
    {
        $this->actingAsAdmin();
        foreach (['94020000', '94020001', '55555555'] as $identifiant) {
            $user = User::factory()->student()->create(['identifiant' => $identifiant]);
            Student::factory()->create(['user_id' => $user->id]);
        }

        $this->get(route('admin.students.index', ['search' => '940200']))
            ->assertViewHas('students', fn ($students) => $students->pluck('user.identifiant')->sort()->values()->all() === ['94020000', '94020001']);

        $this->get(route('admin.students.index', ['search' => '12345']))
            ->assertViewHas('students', fn ($students) => $students->isEmpty());
    }

    public function test_students_can_be_searched_by_name(): void
    {
        $this->actingAsAdmin();
        foreach ([['Marie', 'Curie'], ['Pierre', 'Curie'], ['Marie', 'Dupont'], ['Jean', 'Martin']] as [$first, $last]) {
            Student::factory()->create(['first_name' => $first, 'last_name' => $last]);
        }

        $names = fn (string $search) => $this->get(route('admin.students.index', ['search' => $search]))
            ->viewData('students')->getCollection()->map->full_name->sort()->values()->all();

        $this->assertSame(['Marie Curie', 'Pierre Curie'], $names('curie'));
        $this->assertSame(['Marie Curie'], $names('marie curie'));
        $this->assertSame(['Marie Curie', 'Marie Dupont'], $names('Marie'));
        $this->assertSame([], $names('Durand'));
    }

    public function test_search_is_kept_across_pages(): void
    {
        $this->actingAsAdmin();
        foreach (range(1, 11) as $i) {
            $user = User::factory()->student()->create(['identifiant' => '9402'.str_pad($i, 4, '0', STR_PAD_LEFT)]);
            Student::factory()->create(['user_id' => $user->id]);
        }

        $this->get(route('admin.students.index', ['search' => '9402']))
            ->assertSee('search=9402', false);
    }
}
