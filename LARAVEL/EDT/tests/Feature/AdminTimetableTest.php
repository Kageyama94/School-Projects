<?php

namespace Tests\Feature;

use App\Enums\Level;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Licence;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTimetableTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_the_timetable_of_a_group(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create([
            'licence_id' => Licence::factory()->create(['name' => 'Informatique'])->id,
            'level' => Level::L2,
            'name' => 'Groupe A',
        ]);
        Lesson::factory()->create([
            'group_id' => $group->id,
            'subject_id' => Subject::factory()->create(['name' => 'Algorithmique'])->id,
            'teacher_id' => Teacher::factory()->create(['last_name' => 'Turing'])->id,
            'day_of_week' => 3,
        ]);
        Lesson::factory()->create();

        $this->get(route('admin.groups.show', $group))
            ->assertOk()
            ->assertSee('Informatique · L2 · Groupe A')
            ->assertSee('Algorithmique')
            ->assertSee('Turing')
            ->assertSee('Mercredi')
            ->assertViewHas('lessons', fn ($lessons) => $lessons->count() === 1);
    }

    public function test_admin_can_see_the_timetable_of_a_room(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create(['name' => 'Amphi Curie']);
        $group = Group::factory()->create(['name' => 'Groupe Z']);
        Lesson::factory()->create(['room_id' => $room->id, 'group_id' => $group->id, 'teacher_id' => Teacher::factory()->create(['last_name' => 'Turing'])->id]);
        Lesson::factory()->create();

        $this->get(route('admin.rooms.show', $room))
            ->assertOk()
            ->assertSee('Amphi Curie')
            ->assertSee('Groupe Z')
            ->assertSee('Turing')
            ->assertViewHas('lessons', fn ($lessons) => $lessons->count() === 1);
    }

    public function test_admin_can_see_the_timetable_of_a_teacher(): void
    {
        $this->actingAsAdmin();
        $teacher = Teacher::factory()->create(['first_name' => 'Alan', 'last_name' => 'Turing']);
        $group = Group::factory()->create(['name' => 'Groupe Z']);
        Lesson::factory()->create(['teacher_id' => $teacher->id, 'group_id' => $group->id, 'room_id' => Room::factory()->create(['name' => 'Salle 42'])->id]);
        Lesson::factory()->create();

        $this->get(route('admin.teachers.show', $teacher))
            ->assertOk()
            ->assertSee('Alan Turing')
            ->assertSee('Groupe Z')
            ->assertSee('Salle 42')
            ->assertViewHas('lessons', fn ($lessons) => $lessons->count() === 1);
    }

    public function test_admin_timetables_let_the_admin_remove_a_lesson(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        Lesson::factory()->create(['room_id' => $room->id]);

        $this->get(route('admin.rooms.show', $room))
            ->assertOk()
            ->assertSee('Supprimer ce cours');
    }

    public function test_timetable_pages_are_admin_only(): void
    {
        $this->actingAs(User::factory()->teacher()->create());

        $this->get(route('admin.groups.show', Group::factory()->create()))->assertForbidden();
        $this->get(route('admin.rooms.show', Room::factory()->create()))->assertForbidden();
        $this->get(route('admin.teachers.show', Teacher::factory()->create()))->assertForbidden();
    }

    public function test_lists_link_to_the_timetables(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create();
        $room = Room::factory()->create();
        $teacher = Teacher::factory()->create();

        $this->get(route('admin.licences.index'))->assertSee(route('admin.groups.show', $group));
        $this->get(route('admin.rooms.index'))->assertSee(route('admin.rooms.show', $room));
        $this->get(route('admin.teachers.index'))->assertSee(route('admin.teachers.show', $teacher));
    }
}
