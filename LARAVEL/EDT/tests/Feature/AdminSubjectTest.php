<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_create_a_subject(): void
    {
        $user = User::factory()->teacher()->create();

        $response = $this->actingAs($user)->post(route('admin.subjects.store'), [
            'name' => 'Philosophie',
            'color' => '#3b82f6',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('subjects', ['name' => 'Philosophie']);
    }

    public function test_admin_can_create_a_subject(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.subjects.store'), [
            'name' => 'Philosophie',
            'color' => '#3b82f6',
        ]);

        $response->assertRedirect(route('admin.teachers.index'));
        $this->assertDatabaseHas('subjects', ['name' => 'Philosophie', 'color' => '#3b82f6']);
    }

    public function test_admin_can_delete_an_unused_subject(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = Subject::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.subjects.destroy', $subject));

        $response->assertRedirect(route('admin.teachers.index'));
        $this->assertModelMissing($subject);
    }

    public function test_subject_used_by_lessons_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = Subject::factory()->create();
        Lesson::factory()->create(['subject_id' => $subject->id]);

        $response = $this->actingAs($admin)->delete(route('admin.subjects.destroy', $subject));

        $response->assertSessionHas('error');
        $this->assertModelExists($subject);
    }

    public function test_non_admin_cannot_delete_a_subject(): void
    {
        $user = User::factory()->teacher()->create();
        $subject = Subject::factory()->create();

        $this->actingAs($user)->delete(route('admin.subjects.destroy', $subject))->assertForbidden();

        $this->assertModelExists($subject);
    }

    public function test_subject_name_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Subject::factory()->create(['name' => 'Philosophie']);

        $response = $this->actingAs($admin)->post(route('admin.subjects.store'), [
            'name' => 'Philosophie',
            'color' => '#3b82f6',
        ]);

        $response->assertSessionHasErrors('name');
    }
}
