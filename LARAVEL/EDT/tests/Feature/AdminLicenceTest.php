<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Licence;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLicenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_manage_licences(): void
    {
        $this->actingAs(User::factory()->student()->create());
        $licence = Licence::factory()->create();

        $this->get(route('admin.licences.index'))->assertForbidden();
        $this->post(route('admin.licences.store'), ['name' => 'Informatique'])->assertForbidden();
        $this->put(route('admin.licences.update', $licence), ['name' => 'Autre'])->assertForbidden();
        $this->delete(route('admin.licences.destroy', $licence))->assertForbidden();

        $this->assertModelExists($licence);
    }

    public function test_admin_can_list_licences_with_group_and_student_counts(): void
    {
        $this->actingAsAdmin();
        $licence = Licence::factory()->create(['name' => 'Informatique']);
        $groups = Group::factory(2)->create(['licence_id' => $licence->id]);
        Student::factory(3)->create(['group_id' => $groups[0]->id]);
        Student::factory(2)->create(['group_id' => $groups[1]->id]);
        Student::factory()->create();

        $this->get(route('admin.licences.index'))
            ->assertOk()
            ->assertSee('Informatique')
            ->assertViewHas('licences', fn ($licences) => $licences->firstWhere('id', $licence->id)->groups_count === 2
                && $licences->firstWhere('id', $licence->id)->students_count === 5);
    }

    public function test_admin_can_create_a_licence(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.licences.store'), ['name' => 'Informatique'])
            ->assertRedirect(route('admin.licences.index'));

        $this->assertDatabaseHas('licences', ['name' => 'Informatique']);
    }

    public function test_licence_name_is_required_and_unique(): void
    {
        $this->actingAsAdmin();
        Licence::factory()->create(['name' => 'Informatique']);

        $this->post(route('admin.licences.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->post(route('admin.licences.store'), ['name' => 'Informatique'])->assertSessionHasErrors('name');
    }

    public function test_admin_can_rename_a_licence_and_keep_its_own_name(): void
    {
        $this->actingAsAdmin();
        $licence = Licence::factory()->create(['name' => 'Informatique']);

        $this->put(route('admin.licences.update', $licence), ['name' => 'Informatique'])->assertSessionHasNoErrors();
        $this->put(route('admin.licences.update', $licence), ['name' => 'Mathématiques'])
            ->assertRedirect(route('admin.licences.index'));

        $this->assertSame('Mathématiques', $licence->fresh()->name);
    }

    public function test_admin_can_delete_a_licence_without_groups(): void
    {
        $this->actingAsAdmin();
        $licence = Licence::factory()->create();

        $this->delete(route('admin.licences.destroy', $licence))->assertRedirect(route('admin.licences.index'));

        $this->assertModelMissing($licence);
    }

    public function test_licence_with_groups_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();
        $group = Group::factory()->create();

        $this->delete(route('admin.licences.destroy', $group->licence))->assertSessionHas('error');

        $this->assertModelExists($group->licence);
        $this->assertModelExists($group);
    }
}
