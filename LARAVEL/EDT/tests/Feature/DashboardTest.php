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

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Compte enseignant avec sa fiche et une matière (sans licence : chaque test choisit les siennes).
     *
     * @return array{User, Teacher}
     */
    private function teacherAccount(?Subject $subject = null): array
    {
        $user = User::factory()->teacher()->create();
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $teacher->subjects()->attach($subject ?? Subject::factory()->create());

        return [$user, $teacher];
    }

    public function test_admin_dashboard_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('enseignants');
    }

    public function test_admin_dashboard_counts_students_without_a_group(): void
    {
        $admin = User::factory()->admin()->create();
        Student::factory()->create(['group_id' => null]);
        Student::factory()->create(['group_id' => null]);
        Student::factory()->create();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('counts', fn (array $counts) => $counts['students'] === 3 && $counts['students_without_group'] === 2)
            ->assertSee('étudiants sans groupe')
            ->assertSee('bg-amber-100');
    }

    public function test_admin_dashboard_does_not_warn_when_every_student_has_a_group(): void
    {
        $admin = User::factory()->admin()->create();
        Student::factory()->create();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('counts', fn (array $counts) => $counts['students_without_group'] === 0)
            ->assertDontSee('bg-amber-100');
    }

    public function test_admin_navigation_links_are_available_on_desktop_and_mobile_menus(): void
    {
        $admin = User::factory()->admin()->create();

        $content = $this->actingAs($admin)->get(route('dashboard'))->getContent();

        foreach (['admin.teachers.index', 'admin.students.index', 'admin.licences.index', 'admin.rooms.index'] as $route) {
            $this->assertSame(2, substr_count($content, 'href="'.route($route).'"'), "{$route} should appear in both menus");
        }

        $this->assertSame(2, substr_count($content, 'Licences et groupes'), 'One merged entry per menu');
    }

    public function test_non_admin_navigation_has_no_admin_links(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('dashboard'))->assertDontSee(route('admin.licences.index'));
    }

    public function test_teacher_dashboard_lists_only_their_licences_that_have_groups_with_full_group_labels(): void
    {
        [$user, $teacher] = $this->teacherAccount();
        $info = Licence::factory()->create(['name' => 'Informatique']);
        $physique = Licence::factory()->create(['name' => 'Physique']);
        $maths = Licence::factory()->create(['name' => 'Mathématiques']);
        $empty = Licence::factory()->create(['name' => 'Licence sans groupe']);
        $teacher->licences()->attach([$info->id, $maths->id, $empty->id]);
        Group::factory()->create(['licence_id' => $maths->id, 'level' => Level::L1, 'name' => 'Groupe A']);
        Group::factory()->create(['licence_id' => $info->id, 'level' => Level::L2, 'name' => 'Groupe A']);
        Group::factory()->create(['licence_id' => $info->id, 'level' => Level::L1, 'name' => 'Groupe B']);
        Group::factory()->create(['licence_id' => $physique->id, 'level' => Level::L1, 'name' => 'Groupe A']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('scheduler', fn (array $scheduler) => collect($scheduler['licences'])->pluck('name')->all() === ['Informatique', 'Mathématiques']
                && collect($scheduler['groups'])->pluck('label')->all() === [
                    'Informatique · L1 · Groupe B',
                    'Informatique · L2 · Groupe A',
                    'Mathématiques · L1 · Groupe A',
                ]);
    }

    public function test_teacher_dashboard_lists_the_weekly_recap_before_the_group_grid(): void
    {
        [$user, $teacher] = $this->teacherAccount();
        $teacher->licences()->attach(Group::factory()->create()->licence_id);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Ma semaine', 'Emploi du temps — '])
            ->assertSee(asset('js/teacher-scheduler.js'), false)
            ->assertDontSee('function teacherScheduler', false);
    }

    public function test_free_slots_have_a_distinct_accessible_label(): void
    {
        [$user, $teacher] = $this->teacherAccount();
        $teacher->licences()->attach(Group::factory()->create()->licence_id);

        $content = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Ajouter un cours le Lundi de 08:00 à 09:00"', $content);
        $this->assertStringContainsString('aria-label="Ajouter un cours le Samedi de 11:00 à 12:00"', $content);
        $this->assertStringNotContainsString('aria-label="Ajouter un cours le Samedi de 14:00', $content);
        // 6 jours × 8 créneaux, moins les 4 créneaux de l'après-midi du samedi.
        $this->assertSame(44, substr_count($content, 'aria-label="Ajouter un cours le '));
    }

    public function test_teacher_dashboard_exposes_the_busy_cells_of_their_groups_and_of_all_rooms(): void
    {
        [$user, $teacher] = $this->teacherAccount();
        $groupA = Group::factory()->create();
        $groupB = Group::factory()->create();
        $teacher->licences()->attach($groupA->licence_id);
        $room = Room::factory()->create();
        Lesson::factory()->create(['group_id' => $groupA->id, 'room_id' => $room->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:00']);
        Lesson::factory()->create(['group_id' => $groupA->id, 'room_id' => null, 'day_of_week' => 6, 'start_time' => '11:00', 'end_time' => '12:00']);
        Lesson::factory()->create(['group_id' => $groupB->id, 'room_id' => $room->id, 'day_of_week' => 2, 'start_time' => '14:00', 'end_time' => '15:00']);

        $this->actingAs($user)->get(route('dashboard'))->assertViewHas('scheduler', function (array $scheduler) use ($groupA, $room) {
            $group = $scheduler['groupBusyCells']->map->all()->all();
            $rooms = $scheduler['roomBusyCells']->map->all()->all();

            // Le groupe B n'est pas dans ses licences : seuls ses créneaux de salle comptent.
            return $group === [$groupA->id => ['1-08:00', '6-11:00']]
                && $rooms === [$room->id => ['1-08:00', '2-14:00']];
        });
    }

    public function test_teacher_dashboard_does_not_load_a_model_per_lesson(): void
    {
        [$user, $teacher] = $this->teacherAccount();
        Lesson::factory(30)->create();

        $retrieved = 0;
        Lesson::retrieved(function () use (&$retrieved) {
            $retrieved++;
        });

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->assertSame(0, $retrieved, 'Seuls les cours de l\'enseignant devraient être instanciés (il n\'en a aucun ici).');
    }

    public function test_teacher_dashboard_shows_the_whole_week_across_groups(): void
    {
        $subject = Subject::factory()->create(['name' => 'Algorithmique']);
        [$user, $teacher] = $this->teacherAccount($subject);
        $groupA = Group::factory()->create(['name' => 'Groupe Alpha']);
        $groupB = Group::factory()->create(['name' => 'Groupe Beta']);
        $teacher->licences()->attach([$groupA->licence_id, $groupB->licence_id]);
        Lesson::factory()->create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'group_id' => $groupA->id, 'day_of_week' => 1, 'start_time' => '10:00', 'end_time' => '11:00']);
        Lesson::factory()->create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'group_id' => $groupB->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:00']);
        Lesson::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ma semaine')
            ->assertViewHas('myLessons', fn ($lessons) => $lessons->count() === 2
                && $lessons->pluck('group.name')->all() === ['Groupe Beta', 'Groupe Alpha']);
    }

    public function test_teacher_can_delete_a_lesson_from_the_weekly_recap(): void
    {
        $subject = Subject::factory()->create();
        [$user, $teacher] = $this->teacherAccount($subject);
        $lesson = Lesson::factory()->create(['teacher_id' => $teacher->id, 'subject_id' => $subject->id]);
        $teacher->licences()->attach($lesson->group->licence_id);
        $other = Lesson::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $response->assertSee(route('lessons.destroy', $lesson), false);
        $response->assertSee('Supprimer ce cours');

        $this->delete(route('lessons.destroy', $lesson))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
        $this->assertDatabaseHas('lessons', ['id' => $other->id]);
    }

    public function test_student_dashboard_shows_the_full_group_label(): void
    {
        $user = User::factory()->student()->create();
        $group = Group::factory()->create([
            'licence_id' => Licence::factory()->create(['name' => 'Informatique'])->id,
            'level' => Level::L2,
            'name' => 'Groupe A',
        ]);
        Student::factory()->create(['user_id' => $user->id, 'group_id' => $group->id]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Informatique · L2 · Groupe A');
    }

    public function test_student_dashboard_shows_the_group_schedule_including_saturday(): void
    {
        $user = User::factory()->student()->create();
        $group = Group::factory()->create();
        Student::factory()->create(['user_id' => $user->id, 'group_id' => $group->id]);
        $subject = Subject::factory()->create(['name' => 'Philosophie']);
        Lesson::factory()->create([
            'group_id' => $group->id,
            'subject_id' => $subject->id,
            'day_of_week' => 6,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Samedi')
            ->assertSee('Philosophie');
    }

    public function test_teacher_dashboard_is_displayed_with_rooms_and_group_lessons(): void
    {
        $subject = Subject::factory()->create();
        [$user, $teacher] = $this->teacherAccount($subject);
        $group = Group::factory()->create();
        $teacher->licences()->attach($group->licence_id);
        $room = Room::factory()->create(['name' => 'Salle Curie']);
        Lesson::factory()->create([
            'group_id' => $group->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Salle Curie')
            ->assertSee('2-10:00', false);
    }

    public function test_teacher_without_subject_is_told_to_contact_an_admin(): void
    {
        $user = User::factory()->teacher()->create();
        Teacher::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aucune matière');
    }

    public function test_teacher_without_licence_is_told_to_contact_an_admin(): void
    {
        [$user, $teacher] = $this->teacherAccount();
        Group::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aucune licence ne t\'est encore assignée', false)
            ->assertDontSee('Ajouter un cours');
    }
}
