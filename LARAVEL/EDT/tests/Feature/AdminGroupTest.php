<?php

namespace Tests\Feature;

use App\Enums\Level;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Licence;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_manage_groups(): void
    {
        $this->actingAs(User::factory()->teacher()->create());
        $group = Group::factory()->create();
        $payload = ['licence_id' => $group->licence_id, 'level' => 'l1', 'name' => 'Groupe X'];

        $this->get(route('admin.groups.create'))->assertForbidden();
        $this->post(route('admin.groups.store'), $payload)->assertForbidden();
        $this->put(route('admin.groups.update', $group), $payload)->assertForbidden();
        $this->delete(route('admin.groups.destroy', $group))->assertForbidden();

        $this->assertModelExists($group);
    }

    public function test_groups_are_managed_from_the_licences_page(): void
    {
        $this->actingAsAdmin();
        $licence = Licence::factory()->create(['name' => 'Informatique']);
        $group = Group::factory()->create(['name' => 'Groupe A', 'licence_id' => $licence->id, 'level' => Level::L2]);

        $this->get(route('admin.licences.index'))
            ->assertOk()
            ->assertSee('Informatique')
            ->assertSee('L2')
            ->assertSee('Groupe A')
            ->assertSee(route('admin.groups.show', $group), false)
            ->assertSee(route('admin.groups.edit', $group), false)
            ->assertSee(route('admin.groups.create', ['licence_id' => $licence->id]), false);

        $this->assertFalse(Route::has('admin.groups.index'));
    }

    public function test_groups_are_listed_under_their_licence_by_level_then_name(): void
    {
        $this->actingAsAdmin();
        $maths = Licence::factory()->create(['name' => 'Mathématiques']);
        $info = Licence::factory()->create(['name' => 'Informatique']);
        Group::factory()->create(['licence_id' => $maths->id, 'level' => Level::L1, 'name' => 'B']);
        Group::factory()->create(['licence_id' => $info->id, 'level' => Level::L2, 'name' => 'A']);
        Group::factory()->create(['licence_id' => $info->id, 'level' => Level::L1, 'name' => 'B']);
        Group::factory()->create(['licence_id' => $info->id, 'level' => Level::L1, 'name' => 'A']);
        Licence::factory()->create(['name' => 'Physique']);

        $this->get(route('admin.licences.index'))->assertViewHas(
            'licences',
            fn ($licences) => $licences->pluck('name')->all() === ['Informatique', 'Mathématiques', 'Physique']
                && $licences[0]->groups->map->label->all() === ['Informatique · L1 · A', 'Informatique · L1 · B', 'Informatique · L2 · A']
                && $licences[1]->groups->map->label->all() === ['Mathématiques · L1 · B']
                && $licences[2]->groups->isEmpty()
        )->assertSee('Aucun groupe dans cette licence.');
    }

    public function test_licences_page_shows_group_counts(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create();
        Student::factory(3)->create(['group_id' => $group->id]);
        Lesson::factory(2)->create(['group_id' => $group->id]);

        $this->get(route('admin.licences.index'))->assertViewHas(
            'licences',
            fn ($licences) => $licences[0]->groups_count === 1
                && $licences[0]->students_count === 3
                && $licences[0]->groups[0]->students_count === 3
                && $licences[0]->groups[0]->lessons_count === 2
        );
    }

    public function test_the_licence_is_prefilled_when_adding_a_group_from_a_licence(): void
    {
        $this->actingAsAdmin();
        Licence::factory()->create();
        $target = Licence::factory()->create(['name' => 'Cible']);

        $this->get(route('admin.groups.create', ['licence_id' => $target->id]))
            ->assertOk()
            ->assertSee('value="'.$target->id.'" selected', false);

        $this->get(route('admin.groups.create', ['licence_id' => 9999]))->assertOk();
    }

    public function test_admin_can_create_a_group_in_a_licence_and_level(): void
    {
        $this->actingAsAdmin();
        $licence = Licence::factory()->create();

        $this->post(route('admin.groups.store'), [
            'licence_id' => $licence->id,
            'level' => Level::L3->value,
            'name' => 'Groupe A',
        ])->assertRedirect(route('admin.licences.index'));

        $this->assertDatabaseHas('groups', [
            'licence_id' => $licence->id,
            'level' => 'l3',
            'name' => 'Groupe A',
        ]);
    }

    public function test_group_requires_a_valid_licence_and_level(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.groups.store'), ['name' => 'Groupe A'])
            ->assertSessionHasErrors(['licence_id', 'level']);
        $this->post(route('admin.groups.store'), ['name' => 'Groupe A', 'licence_id' => 999, 'level' => 'l9'])
            ->assertSessionHasErrors(['licence_id', 'level']);

        $this->assertDatabaseCount('groups', 0);
    }

    public function test_group_name_is_unique_within_a_licence_and_level_only(): void
    {
        $this->actingAsAdmin();
        $info = Licence::factory()->create();
        $maths = Licence::factory()->create();
        Group::factory()->create(['licence_id' => $info->id, 'level' => Level::L1, 'name' => 'Groupe A']);

        $this->post(route('admin.groups.store'), ['licence_id' => $info->id, 'level' => 'l1', 'name' => 'Groupe A'])
            ->assertSessionHasErrors('name');

        $this->post(route('admin.groups.store'), ['licence_id' => $info->id, 'level' => 'l2', 'name' => 'Groupe A'])
            ->assertSessionHasNoErrors();
        $this->post(route('admin.groups.store'), ['licence_id' => $maths->id, 'level' => 'l1', 'name' => 'Groupe A'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('groups', 3);
    }

    public function test_admin_can_edit_a_group_and_keep_its_own_name(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create(['level' => Level::L1, 'name' => 'Groupe A']);
        $other = Licence::factory()->create();

        $this->put(route('admin.groups.update', $group), [
            'licence_id' => $group->licence_id,
            'level' => 'l1',
            'name' => 'Groupe A',
        ])->assertSessionHasNoErrors();

        $this->put(route('admin.groups.update', $group), [
            'licence_id' => $other->id,
            'level' => 'm1',
            'name' => 'Groupe B',
        ])->assertRedirect(route('admin.licences.index'));

        $group->refresh();
        $this->assertSame('Groupe B', $group->name);
        $this->assertSame($other->id, $group->licence_id);
        $this->assertSame(Level::M1, $group->level);
    }

    public function test_teachers_of_a_group_follow_it_into_its_new_licence(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create(['level' => Level::L1, 'name' => 'Groupe A']);
        $other = Licence::factory()->create();
        $teacher = Teacher::factory()->create();
        $teacher->licences()->attach([$group->licence_id, $other->id]);
        $newcomer = Teacher::factory()->create();
        $newcomer->licences()->attach($group->licence_id);
        $bystander = Teacher::factory()->create();
        Lesson::factory()->create(['group_id' => $group->id, 'teacher_id' => $teacher->id]);
        Lesson::factory()->create(['group_id' => $group->id, 'teacher_id' => $newcomer->id]);

        $this->put(route('admin.groups.update', $group), [
            'licence_id' => $other->id,
            'level' => 'l1',
            'name' => 'Groupe A',
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([$group->licence_id, $other->id], $teacher->licences()->pluck('licences.id')->all());
        $this->assertEqualsCanonicalizing([$group->licence_id, $other->id], $newcomer->licences()->pluck('licences.id')->all());
        $this->assertTrue($bystander->licences()->doesntExist());
    }

    public function test_group_form_asks_for_confirmation_before_moving_students_to_another_licence_or_level(): void
    {
        $this->actingAsAdmin();
        $withStudents = Group::factory()->create();
        Student::factory(3)->create(['group_id' => $withStudents->id]);
        $teacher = Teacher::factory()->create();
        Lesson::factory(2)->sequence(['day_of_week' => 1], ['day_of_week' => 2])->create(['group_id' => $withStudents->id, 'teacher_id' => $teacher->id]);
        $empty = Group::factory()->create();

        $this->get(route('admin.groups.edit', $withStudents))
            ->assertOk()
            ->assertSee('x-on:submit', false)
            ->assertSee('students: 3', false)
            ->assertSee('teachers: 1', false)
            ->assertSee('étudiant(s) de ce groupe changeront de licence ou de niveau avec lui', false)
            ->assertSee('enseignant(s) qui y ont cours recevront aussi la nouvelle licence', false)
            ->assertSee("licence: '{$withStudents->licence_id}', level: '{$withStudents->level->value}'", false);

        // Sans étudiant (ou à la création), le compteur vaut 0 : la confirmation ne se déclenche jamais.
        $this->get(route('admin.groups.edit', $empty))->assertOk()->assertSee('students: 0, teachers: 0', false);
        $this->get(route('admin.groups.create'))->assertOk()->assertSee('students: 0, teachers: 0', false);
    }

    public function test_flash_messages_are_displayed_in_the_layout(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create();
        Student::factory()->create(['group_id' => $group->id]);

        $this->followingRedirects()->post(route('admin.groups.store'), [
            'licence_id' => $group->licence_id,
            'level' => 'l1',
            'name' => 'Nouveau',
        ])->assertSee('Groupe créé.')->assertSee('bg-green-50', false);

        $this->followingRedirects()->delete(route('admin.groups.destroy', $group))
            ->assertSee('impossible de le supprimer')
            ->assertSee('bg-red-50', false);
    }

    public function test_group_form_asks_to_create_a_licence_first_when_none_exists(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.groups.create'))->assertOk()->assertSee('Crée d\'abord une', false);
    }

    public function test_admin_can_delete_an_unused_group(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create();

        $this->delete(route('admin.groups.destroy', $group))->assertRedirect(route('admin.licences.index'));

        $this->assertModelMissing($group);
    }

    public function test_group_with_students_or_lessons_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();
        $withStudent = Group::factory()->create();
        Student::factory()->create(['group_id' => $withStudent->id]);
        $withLesson = Group::factory()->create();
        Lesson::factory()->create(['group_id' => $withLesson->id]);

        $this->delete(route('admin.groups.destroy', $withStudent))->assertSessionHas('error');
        $this->delete(route('admin.groups.destroy', $withLesson))->assertSessionHas('error');

        $this->assertModelExists($withStudent);
        $this->assertModelExists($withLesson);
    }
}
