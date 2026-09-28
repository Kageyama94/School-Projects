<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTeacherTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_teacher_without_a_subject(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('admin.teachers.store'), [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);

        $response->assertRedirect(route('admin.teachers.index'));

        $teacher = Teacher::where('first_name', 'Jean')->where('last_name', 'Dupont')->firstOrFail();
        $this->assertNotNull($teacher->user_id);
        $this->assertTrue($teacher->user->must_change_password);
        $this->assertTrue(Hash::check('dupont_jean', $teacher->user->password));
        $this->assertTrue($teacher->subjects->isEmpty());
    }

    public function test_admin_can_save_a_teacher_with_zero_subjects(): void
    {
        $this->actingAsAdmin();
        $subject = Subject::factory()->create();
        $teacher = Teacher::factory()->create();
        $teacher->subjects()->attach($subject);

        $this->put(route('admin.teachers.update', $teacher), [
            'first_name' => $teacher->first_name,
            'last_name' => $teacher->last_name,
        ])->assertSessionHasNoErrors();

        $this->assertTrue($teacher->subjects()->get()->isEmpty());
    }

    public function test_admin_can_update_a_teachers_profile_and_subjects(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->teacher()->create(['identifiant' => '94020000', 'name' => 'Jean Dupond']);
        $teacher = Teacher::factory()->create(['user_id' => $user->id, 'first_name' => 'Jean', 'last_name' => 'Dupond']);
        $teacher->subjects()->attach(Subject::factory()->create());
        $newSubject = Subject::factory()->create();

        $response = $this->put(route('admin.teachers.update', $teacher), [
            'first_name' => 'Jeanne',
            'last_name' => 'Dupont',
            'identifiant' => '94020099',
            'subjects' => [$newSubject->id],
        ]);

        $response->assertRedirect(route('admin.teachers.index'));
        $teacher->refresh();
        $this->assertSame('Jeanne', $teacher->first_name);
        $this->assertSame('Dupont', $teacher->last_name);
        $this->assertEquals([$newSubject->id], $teacher->subjects()->pluck('subjects.id')->all());
        $user->refresh();
        $this->assertSame('Jeanne Dupont', $user->name);
        $this->assertSame('94020099', $user->identifiant);
    }

    public function test_admin_can_update_a_teacher_without_account(): void
    {
        $this->actingAsAdmin();
        $teacher = Teacher::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupond']);
        $subject = Subject::factory()->create();

        $this->put(route('admin.teachers.update', $teacher), [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'subjects' => [$subject->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame('Dupont', $teacher->fresh()->last_name);
    }

    public function test_admin_can_delete_a_teacher_with_their_account_and_lessons(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->teacher()->create();
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $lesson = Lesson::factory()->create(['teacher_id' => $teacher->id]);

        $response = $this->delete(route('admin.teachers.destroy', $teacher));

        $response->assertRedirect(route('admin.teachers.index'));
        $this->assertModelMissing($teacher);
        $this->assertModelMissing($user);
        $this->assertModelMissing($lesson);
    }

    public function test_teacher_list_is_paginated_by_10(): void
    {
        $this->actingAsAdmin();
        Teacher::factory(11)->create();

        $this->get(route('admin.teachers.index'))
            ->assertViewHas('teachers', fn ($teachers) => $teachers->count() === 10 && $teachers->total() === 11);
    }

    public function test_subject_list_is_paginated_by_10_with_its_own_page_parameter(): void
    {
        $this->actingAsAdmin();
        Subject::factory(11)->create();

        $this->get(route('admin.teachers.index'))
            ->assertViewHas('subjects', fn ($subjects) => $subjects->count() === 10 && $subjects->total() === 11);

        $this->get(route('admin.teachers.index', ['subjects_page' => 2]))
            ->assertViewHas('subjects', fn ($subjects) => $subjects->count() === 1 && $subjects->currentPage() === 2);
    }

    public function test_teacher_and_subject_pagination_do_not_interfere_with_each_other(): void
    {
        $this->actingAsAdmin();
        Teacher::factory(15)->create();
        Subject::factory(15)->create();

        $this->get(route('admin.teachers.index', ['page' => 2, 'subjects_page' => 2]))
            ->assertViewHas('teachers', fn ($teachers) => $teachers->currentPage() === 2 && $teachers->count() === 5)
            ->assertViewHas('subjects', fn ($subjects) => $subjects->currentPage() === 2 && $subjects->count() === 5)
            ->assertSee('subjects_page=2', false)
            ->assertSee('page=2', false);
    }

    public function test_teachers_can_be_searched_by_first_or_last_name(): void
    {
        $this->actingAsAdmin();
        Teacher::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);
        Teacher::factory()->create(['first_name' => 'Marie', 'last_name' => 'Curie']);
        Teacher::factory()->create(['first_name' => 'Paul', 'last_name' => 'Jeanson']);

        $search = function (string $term) {
            $view = $this->get(route('admin.teachers.index', ['search' => $term]))->viewData('teachers');

            return $view->pluck('last_name')->sort()->values()->all();
        };

        $this->assertSame(['Dupont', 'Jeanson'], $search('jean'));
        $this->assertSame(['Curie'], $search('CURIE'));
        $this->assertSame(['Curie'], $search('mar'));
        $this->assertSame(['Dupont'], $search('jean dup'));
        $this->assertSame(['Dupont'], $search('  Dupont   Jean '));
        $this->assertSame([], $search('inconnu'));
        $this->assertSame(['Curie', 'Dupont', 'Jeanson'], $search(''));
    }
}
