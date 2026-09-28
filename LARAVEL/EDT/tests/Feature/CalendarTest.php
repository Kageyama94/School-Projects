<?php

namespace Tests\Feature;

use App\Enums\Level;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Licence;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private function studentInGroup(Group $group): User
    {
        $user = User::factory()->student()->create();
        Student::factory()->create(['user_id' => $user->id, 'group_id' => $group->id]);

        return $user;
    }

    public function test_student_downloads_the_calendar_of_their_group(): void
    {
        $group = Group::factory()->create([
            'licence_id' => Licence::factory()->create(['name' => 'Informatique'])->id,
            'level' => Level::L2,
            'name' => 'Groupe A',
        ]);
        Lesson::factory()->create([
            'group_id' => $group->id,
            'subject_id' => Subject::factory()->create(['name' => 'Algorithmique'])->id,
            'teacher_id' => Teacher::factory()->create(['first_name' => 'Alan', 'last_name' => 'Turing'])->id,
            'room_id' => Room::factory()->create(['name' => 'Salle 42'])->id,
            'day_of_week' => 3,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);
        Lesson::factory()->create();

        $response = $this->actingAs($this->studentInGroup($group))->get(route('calendar'));

        $response->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $this->assertStringContainsString('attachment; filename="emploi-du-temps.ics"', $response->headers->get('Content-Disposition'));

        $ics = $response->getContent();
        $unfolded = str_replace("\r\n ", '', $ics);
        $wednesday = now()->startOfWeek()->addDays(2)->format('Ymd');

        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
        $this->assertSame(1, substr_count($ics, 'BEGIN:VEVENT'));
        $this->assertStringContainsString("DTSTART:{$wednesday}T100000", $ics);
        $this->assertStringContainsString("DTEND:{$wednesday}T110000", $ics);
        $this->assertStringContainsString('RRULE:FREQ=WEEKLY;BYDAY=WE', $ics);
        $this->assertStringContainsString("SUMMARY:Algorithmique\r\n", $ics);
        $this->assertStringContainsString("LOCATION:Salle 42\r\n", $ics);
        $this->assertStringContainsString('Alan Turing', $unfolded);
        $this->assertStringContainsString('X-WR-CALNAME:Emploi du temps — Informatique · L2 · Groupe A', $unfolded);
    }

    public function test_teacher_downloads_only_their_own_lessons_with_the_group_label(): void
    {
        $user = User::factory()->teacher()->create();
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $group = Group::factory()->create(['name' => 'Groupe Alpha']);
        Lesson::factory()->create(['teacher_id' => $teacher->id, 'group_id' => $group->id, 'day_of_week' => 1]);
        Lesson::factory()->create(['teacher_id' => $teacher->id, 'day_of_week' => 6]);
        Lesson::factory()->create();

        $ics = $this->actingAs($user)->get(route('calendar'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($ics, 'BEGIN:VEVENT'));
        $this->assertStringContainsString('Groupe Alpha', str_replace("\r\n ", '', $ics));
        $this->assertStringContainsString('BYDAY=MO', $ics);
        $this->assertStringContainsString('BYDAY=SA', $ics);
    }

    public function test_special_characters_are_escaped_and_long_lines_are_folded(): void
    {
        $group = Group::factory()->create();
        Lesson::factory()->create([
            'group_id' => $group->id,
            'subject_id' => Subject::factory()->create(['name' => 'Histoire, Géographie; et « civilisations » '.str_repeat('très longue ', 12)])->id,
        ]);

        $ics = $this->actingAs($this->studentInGroup($group))->get(route('calendar'))->getContent();

        $this->assertStringContainsString('Histoire\, Géographie\; et', str_replace("\r\n ", '', $ics));
        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line), "Ligne trop longue : {$line}");
        }
        $this->assertTrue(mb_check_encoding($ics, 'UTF-8'));
    }

    public function test_calendar_is_only_available_to_students_with_a_group_and_teachers(): void
    {
        $this->get(route('calendar'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->admin()->create())->get(route('calendar'))->assertNotFound();

        $withoutGroup = User::factory()->student()->create();
        Student::factory()->create(['user_id' => $withoutGroup->id, 'group_id' => null]);
        $this->actingAs($withoutGroup)->get(route('calendar'))->assertNotFound();

        $withoutRecord = User::factory()->teacher()->create();
        $this->actingAs($withoutRecord)->get(route('calendar'))->assertNotFound();
    }

    public function test_dashboards_offer_the_calendar_export_but_no_print_button(): void
    {
        $student = $this->studentInGroup(Group::factory()->create());
        $teacherUser = User::factory()->teacher()->create();
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $teacher->subjects()->attach(Subject::factory()->create());
        Group::factory()->create();

        foreach ([$student, $teacherUser] as $user) {
            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertSee(route('calendar'), false)
                ->assertDontSee('window.print()', false)
                ->assertDontSee('Imprimer');
        }
    }
}
