<?php

namespace Tests\Feature;

use App\Actions\BuildTeacherSchedule;
use App\Enums\Level;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Licence;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildTeacherScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_without_a_teacher_record_is_empty_but_valid(): void
    {
        Group::factory()->create();

        $data = app(BuildTeacherSchedule::class)->handle(null);

        $this->assertNull($data['teacher']);
        $this->assertTrue($data['subjects']->isEmpty());
        $this->assertTrue($data['teacherByCell']->isEmpty());
        $this->assertTrue($data['myLessons']->isEmpty());
        $this->assertCount(1, $data['scheduler']['groups']);
    }

    public function test_the_teachers_lessons_are_keyed_by_cell_and_sorted_by_start_time(): void
    {
        $teacher = Teacher::factory()->create();
        $subject = Subject::factory()->create();
        $teacher->subjects()->attach($subject);
        Lesson::factory()->create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'day_of_week' => 2, 'start_time' => '15:00', 'end_time' => '16:00']);
        Lesson::factory()->create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:00']);
        Lesson::factory()->create();

        $data = app(BuildTeacherSchedule::class)->handle($teacher);

        $this->assertEqualsCanonicalizing(['2-15:00', '1-08:00'], $data['teacherByCell']->keys()->all());
        $this->assertSame(['08:00', '15:00'], $data['myLessons']->map(fn (Lesson $lesson) => $lesson->start_time->format('H:i'))->all());
        $this->assertTrue($data['myLessons']->every(fn (Lesson $lesson) => $lesson->relationLoaded('group') && $lesson->group->relationLoaded('licence')));
        $this->assertSame([$subject->id], $data['subjects']->pluck('id')->all());
    }

    public function test_scheduler_config_lists_ordered_groups_licences_rooms_and_busy_cells(): void
    {
        $maths = Licence::factory()->create(['name' => 'Mathématiques']);
        $info = Licence::factory()->create(['name' => 'Informatique']);
        Licence::factory()->create(['name' => 'Sans groupe']);
        $b = Group::factory()->create(['licence_id' => $maths->id, 'level' => Level::L1, 'name' => 'B']);
        $a = Group::factory()->create(['licence_id' => $info->id, 'level' => Level::L2, 'name' => 'A']);
        $room = Room::factory()->create(['name' => 'Amphi', 'capacity' => 120]);
        Lesson::factory()->create(['group_id' => $a->id, 'room_id' => $room->id, 'day_of_week' => 3, 'start_time' => '10:00', 'end_time' => '11:00']);
        Lesson::factory()->create(['group_id' => $b->id, 'room_id' => null, 'day_of_week' => 6, 'start_time' => '09:00', 'end_time' => '10:00']);

        $scheduler = app(BuildTeacherSchedule::class)->handle(null)['scheduler'];

        $this->assertSame(['Informatique', 'Mathématiques'], $scheduler['licences']->pluck('name')->all());
        $this->assertSame(['Informatique · L2 · A', 'Mathématiques · L1 · B'], $scheduler['groups']->pluck('label')->all());
        $this->assertSame([$room->id], $scheduler['rooms']->pluck('id')->all());
        $this->assertSame('Amphi (Salle, 120 places)', $scheduler['rooms'][0]['label']);
        $this->assertSame([$a->id => ['3-10:00'], $b->id => ['6-09:00']], $scheduler['groupBusyCells']->map->all()->all());
        $this->assertSame([$room->id => ['3-10:00']], $scheduler['roomBusyCells']->map->all()->all());
        $this->assertSame([1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'], $scheduler['dayLabels']->all());
    }
}
