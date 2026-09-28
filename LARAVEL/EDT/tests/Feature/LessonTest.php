<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lesson;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(?Subject $subject = null): Teacher
    {
        $user = User::factory()->teacher()->create();
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $teacher->subjects()->attach($subject ?? Subject::factory()->create());

        $this->actingAs($user);

        return $teacher;
    }

    public function test_teacher_can_create_a_lesson(): void
    {
        $subject = Subject::factory()->create();
        $teacher = $this->actingAsTeacher($subject);
        $group = Group::factory()->create();

        $response = $this->post(route('lessons.store'), [
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'room_id' => '',
            'day_of_week' => 1,
            'slot' => 0,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lessons', [
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'day_of_week' => 1,
        ]);
    }

    public function test_non_teacher_cannot_create_a_lesson(): void
    {
        $user = User::factory()->student()->create();
        $group = Group::factory()->create();
        $subject = Subject::factory()->create();

        $response = $this->actingAs($user)->post(route('lessons.store'), [
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'room_id' => '',
            'day_of_week' => 1,
            'slot' => 0,
        ]);

        $response->assertForbidden();
    }

    public function test_teacher_cannot_use_a_subject_not_assigned_to_them(): void
    {
        $this->actingAsTeacher();
        $group = Group::factory()->create();
        $otherSubject = Subject::factory()->create();

        $response = $this->post(route('lessons.store'), [
            'group_id' => $group->id,
            'subject_id' => $otherSubject->id,
            'room_id' => '',
            'day_of_week' => 1,
            'slot' => 0,
        ]);

        $response->assertSessionHasErrors('subject_id');
    }

    public function test_teacher_cannot_double_book_themselves(): void
    {
        $subject = Subject::factory()->create();
        $teacher = $this->actingAsTeacher($subject);
        $group = Group::factory()->create();

        Lesson::factory()->create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response = $this->post(route('lessons.store'), [
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'room_id' => '',
            'day_of_week' => 1,
            'slot' => 0,
        ]);

        $response->assertSessionHasErrors('slot');
    }

    public function test_group_cannot_be_double_booked(): void
    {
        $subject = Subject::factory()->create();
        $teacher = $this->actingAsTeacher($subject);
        $group = Group::factory()->create();

        Lesson::factory()->create([
            'group_id' => $group->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response = $this->post(route('lessons.store'), [
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'room_id' => '',
            'day_of_week' => 1,
            'slot' => 0,
        ]);

        $response->assertSessionHasErrors('group_id');
        $this->assertDatabaseMissing('lessons', ['teacher_id' => $teacher->id]);
    }

    public function test_room_cannot_be_double_booked(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsTeacher($subject);
        $group = Group::factory()->create();
        $room = Room::factory()->create();

        Lesson::factory()->create([
            'room_id' => $room->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response = $this->post(route('lessons.store'), [
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'day_of_week' => 1,
            'slot' => 0,
        ]);

        $response->assertSessionHasErrors('room_id');
    }

    public function test_room_must_be_large_enough_for_the_group(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsTeacher($subject);
        $group = Group::factory()->create();
        Student::factory(10)->create(['group_id' => $group->id]);
        $room = Room::factory()->create(['capacity' => 5]);

        $response = $this->post(route('lessons.store'), [
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'day_of_week' => 1,
            'slot' => 0,
        ]);

        $response->assertSessionHasErrors('room_id');
    }

    public function test_a_slot_taken_between_validation_and_insert_returns_a_validation_error(): void
    {
        $subject = Subject::factory()->create();
        $teacher = $this->actingAsTeacher($subject);
        $group = Group::factory()->create();
        $otherGroup = Group::factory()->create();

        Lesson::creating(function () use ($teacher, $subject, $otherGroup) {
            DB::table('lessons')->insert([
                'group_id' => $otherGroup->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'day_of_week' => 1,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ]);
        });

        $response = $this->post(route('lessons.store'), [
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'room_id' => '',
            'day_of_week' => 1,
            'slot' => 0,
        ]);

        $response->assertSessionHasErrors('slot');
        $this->assertDatabaseCount('lessons', 1);
    }

    public function test_sunday_is_not_bookable(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsTeacher($subject);
        $group = Group::factory()->create();

        $response = $this->post(route('lessons.store'), [
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'room_id' => '',
            'day_of_week' => 7,
            'slot' => 0,
        ]);

        $response->assertSessionHasErrors('day_of_week');
        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_saturday_afternoon_is_not_bookable(): void
    {
        $subject = Subject::factory()->create();
        $this->actingAsTeacher($subject);
        $group = Group::factory()->create();

        $response = $this->post(route('lessons.store'), [
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'room_id' => '',
            'day_of_week' => 6,
            'slot' => 4,
        ]);

        $response->assertSessionHasErrors('slot');
        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_saturday_morning_is_bookable(): void
    {
        $subject = Subject::factory()->create();
        $teacher = $this->actingAsTeacher($subject);
        $group = Group::factory()->create();

        $response = $this->post(route('lessons.store'), [
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'room_id' => '',
            'day_of_week' => 6,
            'slot' => 0,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lessons', [
            'teacher_id' => $teacher->id,
            'day_of_week' => 6,
        ]);
    }

    public function test_admin_can_delete_any_lesson(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $lesson = Lesson::factory()->create();

        $this->delete(route('lessons.destroy', $lesson))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    public function test_student_cannot_delete_a_lesson(): void
    {
        $this->actingAs(User::factory()->student()->create());
        $lesson = Lesson::factory()->create();

        $this->delete(route('lessons.destroy', $lesson))->assertForbidden();

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
    }

    public function test_teacher_can_delete_their_own_lesson(): void
    {
        $teacher = $this->actingAsTeacher();
        $lesson = Lesson::factory()->create(['teacher_id' => $teacher->id]);

        $response = $this->delete(route('lessons.destroy', $lesson));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    public function test_teacher_cannot_delete_another_teachers_lesson(): void
    {
        $this->actingAsTeacher();
        $otherTeacher = Teacher::factory()->create();
        $lesson = Lesson::factory()->create(['teacher_id' => $otherTeacher->id]);

        $response = $this->delete(route('lessons.destroy', $lesson));

        $response->assertForbidden();
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
    }
}
