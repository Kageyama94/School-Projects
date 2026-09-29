<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lesson;
use App\Models\Licence;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTeacherLessonsTest extends TestCase
{
    use RefreshDatabase;

    private ?Licence $licence = null;

    /**
     * Licence commune aux enseignants et aux groupes de chaque test.
     */
    private function licence(): Licence
    {
        return $this->licence ??= Licence::factory()->create(['name' => 'Informatique']);
    }

    private function teacherOf(Subject ...$subjects): Teacher
    {
        $teacher = Teacher::factory()->create();
        $teacher->subjects()->attach(collect($subjects)->pluck('id'));
        $teacher->licences()->attach($this->licence());

        return $teacher;
    }

    private function lesson(Teacher $teacher, Subject $subject, int $day = 1, string $start = '08:00', string $end = '09:00'): Lesson
    {
        return Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'group_id' => Group::factory()->create(['licence_id' => $this->licence()->id])->id,
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    public function test_all_lessons_can_be_handed_over_to_a_replacement(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create();
        $french = Subject::factory()->create();
        $from = $this->teacherOf($maths, $french);
        $to = $this->teacherOf($maths, $french);
        $lessons = [$this->lesson($from, $maths, 1, '08:00', '09:00'), $this->lesson($from, $french, 2, '10:00', '11:00')];

        $this->post(route('admin.teachers.transfer', $from), ['replacement_id' => $to->id])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.teachers.edit', $from))
            ->assertSessionHas('success');

        foreach ($lessons as $lesson) {
            $this->assertSame($to->id, $lesson->fresh()->teacher_id);
        }
        $this->assertModelExists($from);
    }

    public function test_only_the_chosen_subject_is_handed_over(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create();
        $french = Subject::factory()->create();
        $from = $this->teacherOf($maths, $french);
        $to = $this->teacherOf($maths);
        $mathsLesson = $this->lesson($from, $maths, 1, '08:00', '09:00');
        $frenchLesson = $this->lesson($from, $french, 2, '10:00', '11:00');

        $this->post(route('admin.teachers.transfer', $from), ['replacement_id' => $to->id, 'subject_id' => $maths->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($to->id, $mathsLesson->fresh()->teacher_id);
        $this->assertSame($from->id, $frenchLesson->fresh()->teacher_id);
    }

    public function test_replacement_must_teach_the_subjects_involved(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create(['name' => 'Mathématiques']);
        $from = $this->teacherOf($maths);
        $to = $this->teacherOf(Subject::factory()->create());
        $lesson = $this->lesson($from, $maths);

        $this->post(route('admin.teachers.transfer', $from), ['replacement_id' => $to->id])
            ->assertSessionHasErrors('replacement_id');

        $this->assertSame($from->id, $lesson->fresh()->teacher_id);
        $this->assertStringContainsString('Mathématiques', session('errors')->first('replacement_id'));
    }

    public function test_replacement_must_be_free_on_every_slot(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create();
        $from = $this->teacherOf($maths);
        $to = $this->teacherOf($maths);
        $free = $this->lesson($from, $maths, 1, '10:00', '11:00');
        $clash = $this->lesson($from, $maths, 2, '08:00', '09:00');
        $this->lesson($to, $maths, 2, '08:00', '09:00');

        $this->post(route('admin.teachers.transfer', $from), ['replacement_id' => $to->id])
            ->assertSessionHasErrors('replacement_id');

        $this->assertSame($from->id, $free->fresh()->teacher_id);
        $this->assertSame($from->id, $clash->fresh()->teacher_id);
        $this->assertStringContainsString('Mardi 08:00', session('errors')->first('replacement_id'));
    }

    public function test_replacement_must_have_access_to_the_licences_involved(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create();
        $from = $this->teacherOf($maths);
        $to = $this->teacherOf($maths);
        $to->licences()->detach();
        $lesson = $this->lesson($from, $maths);

        $this->post(route('admin.teachers.transfer', $from), ['replacement_id' => $to->id])
            ->assertSessionHasErrors('replacement_id');

        $this->assertSame($from->id, $lesson->fresh()->teacher_id);
        $this->assertStringContainsString('Informatique', session('errors')->first('replacement_id'));
    }

    public function test_lessons_cannot_be_handed_over_to_the_same_teacher_or_when_there_are_none(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create();
        $teacher = $this->teacherOf($maths);
        $other = $this->teacherOf($maths);

        $this->post(route('admin.teachers.transfer', $teacher), ['replacement_id' => $teacher->id])
            ->assertSessionHasErrors('replacement_id');

        $this->post(route('admin.teachers.transfer', $teacher), ['replacement_id' => $other->id])
            ->assertSessionHasErrors('replacement_id');
    }

    public function test_a_subject_with_lessons_cannot_be_removed_from_a_teacher(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create(['name' => 'Mathématiques']);
        $french = Subject::factory()->create();
        $teacher = $this->teacherOf($maths, $french);
        $this->lesson($teacher, $maths);
        $this->lesson($teacher, $maths, 2);

        $this->put(route('admin.teachers.update', $teacher), [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'subjects' => [$french->id],
            'licences' => [$this->licence()->id],
        ])->assertSessionHasErrors('subjects');

        $this->assertEqualsCanonicalizing([$maths->id, $french->id], $teacher->subjects()->pluck('subjects.id')->all());
        $this->assertStringContainsString('Mathématiques (2 cours)', session('errors')->first('subjects'));
    }

    public function test_a_licence_with_lessons_cannot_be_removed_from_a_teacher(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create();
        $teacher = $this->teacherOf($maths);
        $this->lesson($teacher, $maths);
        $empty = Licence::factory()->create();
        $teacher->licences()->attach($empty);

        $this->put(route('admin.teachers.update', $teacher), [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'subjects' => [$maths->id],
            'licences' => [$empty->id],
        ])->assertSessionHasErrors('licences');

        $this->assertEqualsCanonicalizing([$this->licence()->id, $empty->id], $teacher->licences()->pluck('licences.id')->all());
        $this->assertStringContainsString('Informatique (1 cours)', session('errors')->first('licences'));

        $this->put(route('admin.teachers.update', $teacher), [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'subjects' => [$maths->id],
            'licences' => [$this->licence()->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame([$this->licence()->id], $teacher->licences()->pluck('licences.id')->all());
    }

    public function test_a_subject_can_be_removed_once_its_lessons_have_been_handed_over(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create();
        $french = Subject::factory()->create();
        $teacher = $this->teacherOf($maths, $french);
        $replacement = $this->teacherOf($maths);
        $this->lesson($teacher, $maths);

        $this->post(route('admin.teachers.transfer', $teacher), ['replacement_id' => $replacement->id, 'subject_id' => $maths->id])
            ->assertSessionHasNoErrors();

        $this->put(route('admin.teachers.update', $teacher), [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'subjects' => [$french->id],
            'licences' => [$this->licence()->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame([$french->id], $teacher->subjects()->pluck('subjects.id')->all());
    }

    public function test_a_subject_without_lessons_can_be_removed_freely(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create();
        $french = Subject::factory()->create();
        $teacher = $this->teacherOf($maths, $french);
        $this->lesson($teacher, $french);

        $this->put(route('admin.teachers.update', $teacher), [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'subjects' => [$french->id],
            'licences' => [$this->licence()->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame([$french->id], $teacher->subjects()->pluck('subjects.id')->all());
    }

    public function test_edit_page_offers_the_transfer_form_only_when_relevant(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create(['name' => 'Mathématiques']);
        $teacher = $this->teacherOf($maths);
        $this->teacherOf($maths);

        $this->get(route('admin.teachers.edit', $teacher))
            ->assertOk()
            ->assertSee('aucun cours pour le moment')
            ->assertViewHas('replacements', fn ($replacements) => $replacements->isEmpty());

        $this->lesson($teacher, $maths);

        $this->get(route('admin.teachers.edit', $teacher))
            ->assertOk()
            ->assertSee('Confier les cours')
            ->assertSee('Mathématiques (1)')
            ->assertViewHas('replacements', fn ($replacements) => $replacements->isNotEmpty());
    }

    public function test_only_teachers_able_to_take_over_are_offered_as_replacements(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create();
        $french = Subject::factory()->create();
        $teacher = $this->teacherOf($maths, $french);
        $this->lesson($teacher, $maths);
        $this->lesson($teacher, $french, 2);
        $both = $this->teacherOf($maths, $french);
        $mathsOnly = $this->teacherOf($maths);
        $noLicence = $this->teacherOf($maths, $french);
        $noLicence->licences()->detach();
        $otherSubject = $this->teacherOf(Subject::factory()->create());

        $this->get(route('admin.teachers.edit', $teacher))
            ->assertOk()
            ->assertViewHas('replacements', function ($replacements) use ($both, $mathsOnly, $maths) {
                $takes = $replacements->mapWithKeys(fn ($replacement) => [$replacement->id => $replacement->takes]);

                return $takes->keys()->sort()->values()->all() === collect([$both->id, $mathsOnly->id])->sort()->values()->all()
                    && $takes[$both->id]['all'] === true
                    && $takes[$mathsOnly->id] === ['all' => false, 'subjects' => [$maths->id]];
            })
            ->assertSee('x-bind:disabled', false);
    }

    public function test_the_transfer_form_explains_when_nobody_can_take_over(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create();
        $teacher = $this->teacherOf($maths);
        $this->lesson($teacher, $maths);
        $this->teacherOf(Subject::factory()->create());

        $this->get(route('admin.teachers.edit', $teacher))
            ->assertOk()
            ->assertSee("Aucun autre enseignant n'enseigne ses matières", false);
    }

    public function test_teacher_list_shows_lesson_counts(): void
    {
        $this->actingAsAdmin();
        $maths = Subject::factory()->create();
        $teacher = $this->teacherOf($maths);
        $this->lesson($teacher, $maths);
        $this->lesson($teacher, $maths, 2);

        $this->get(route('admin.teachers.index'))
            ->assertViewHas('teachers', fn ($teachers) => $teachers->first()->lessons_count === 2);
    }

    public function test_non_admin_cannot_transfer_lessons(): void
    {
        $this->actingAs(User::factory()->teacher()->create());
        $maths = Subject::factory()->create();
        $from = $this->teacherOf($maths);
        $to = $this->teacherOf($maths);
        $lesson = $this->lesson($from, $maths);

        $this->post(route('admin.teachers.transfer', $from), ['replacement_id' => $to->id])->assertForbidden();

        $this->assertSame($from->id, $lesson->fresh()->teacher_id);
    }
}
