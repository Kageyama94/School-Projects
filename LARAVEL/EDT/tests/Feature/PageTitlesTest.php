<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTitlesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_have_their_own_title(): void
    {
        $this->get(route('login'))->assertSee('<title>Connexion — EDT</title>', false);

        $user = User::factory()->teacher()->create(['must_change_password' => true]);
        $this->actingAs($user)->get(route('password.force-change'))
            ->assertSee('<title>Nouveau mot de passe — EDT</title>', false);

        $this->post('/logout');
        $this->get('/')->assertSee('<title>EDT</title>', false);
    }

    public function test_admin_pages_have_a_title_matching_their_heading(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $teacher = Teacher::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);

        $pages = [
            route('dashboard') => 'Tableau de bord',
            route('admin.teachers.index') => 'Enseignants',
            route('admin.students.index') => 'Étudiants',
            route('admin.licences.index') => 'Licences et groupes',
            route('admin.rooms.index') => 'Salles',
            route('profile.edit') => 'Profil',
            route('admin.teachers.create') => 'Ajouter un enseignant',
            route('admin.teachers.edit', $teacher) => 'Modifier Jean Dupont',
            route('admin.teachers.show', $teacher) => 'Emploi du temps — Jean Dupont',
            route('admin.rooms.show', Room::factory()->create(['name' => 'Salle 42'])) => 'Emploi du temps — Salle 42',
            route('admin.groups.show', Group::factory()->create()) => 'Emploi du temps — ',
        ];

        foreach ($pages as $url => $title) {
            $content = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('<title>'.e($title), $content, "Titre attendu pour {$url}");
            $this->assertStringContainsString('— EDT</title>', $content);
            $this->assertMatchesRegularExpression('~<h2[^>]*>\s*'.preg_quote(e($title), '~').'~', $content, "En-tête attendu pour {$url}");
        }
    }

    public function test_teacher_and_student_dashboards_are_titled(): void
    {
        $this->actingAs(User::factory()->student()->create())->get(route('dashboard'))
            ->assertSee('<title>Mon emploi du temps — EDT</title>', false);

        $this->actingAs(User::factory()->teacher()->create())->get(route('dashboard'))
            ->assertSee('<title>Mon emploi du temps — EDT</title>', false);
    }
}
