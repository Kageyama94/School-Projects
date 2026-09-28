<?php

namespace Tests\Feature;

use App\Enums\RoomType;
use App\Models\Lesson;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_manage_rooms(): void
    {
        $this->actingAs(User::factory()->student()->create());
        $room = Room::factory()->create();

        $this->get(route('admin.rooms.index'))->assertForbidden();
        $this->post(route('admin.rooms.store'), ['name' => 'Salle X', 'type' => 'salle'])->assertForbidden();
        $this->delete(route('admin.rooms.destroy', $room))->assertForbidden();

        $this->assertModelExists($room);
    }

    public function test_admin_can_list_rooms(): void
    {
        $this->actingAsAdmin();
        Room::factory()->create(['name' => 'Amphi Curie']);

        $this->get(route('admin.rooms.index'))->assertOk()->assertSee('Amphi Curie');
    }

    public function test_admin_can_create_a_room(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.rooms.store'), [
            'name' => 'Amphi Curie',
            'type' => RoomType::Informatique->value,
            'capacity' => 120,
        ])->assertRedirect(route('admin.rooms.index'));

        $this->assertDatabaseHas('rooms', [
            'name' => 'Amphi Curie',
            'type' => RoomType::Informatique->value,
            'capacity' => 120,
        ]);
    }

    public function test_room_capacity_is_optional_but_must_be_positive(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.rooms.store'), ['name' => 'Salle sans capacité', 'type' => 'salle', 'capacity' => ''])
            ->assertSessionHasNoErrors();
        $this->post(route('admin.rooms.store'), ['name' => 'Salle zéro', 'type' => 'salle', 'capacity' => 0])
            ->assertSessionHasErrors('capacity');

        $this->assertDatabaseHas('rooms', ['name' => 'Salle sans capacité', 'capacity' => null]);
    }

    public function test_room_name_must_be_unique_and_type_valid(): void
    {
        $this->actingAsAdmin();
        Room::factory()->create(['name' => 'Salle A']);

        $this->post(route('admin.rooms.store'), ['name' => 'Salle A', 'type' => 'salle'])->assertSessionHasErrors('name');
        $this->post(route('admin.rooms.store'), ['name' => 'Salle B', 'type' => 'piscine'])->assertSessionHasErrors('type');
    }

    public function test_admin_can_update_a_room(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create(['name' => 'Salle A', 'capacity' => 20]);

        $this->put(route('admin.rooms.update', $room), [
            'name' => 'Salle A',
            'type' => RoomType::Gymnase->value,
            'capacity' => 40,
        ])->assertRedirect(route('admin.rooms.index'));

        $room->refresh();
        $this->assertSame(RoomType::Gymnase, $room->type);
        $this->assertSame(40, $room->capacity);
    }

    public function test_admin_can_delete_an_unused_room(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();

        $this->delete(route('admin.rooms.destroy', $room))->assertRedirect(route('admin.rooms.index'));

        $this->assertModelMissing($room);
    }

    public function test_room_used_by_lessons_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create();
        Lesson::factory()->create(['room_id' => $room->id]);

        $this->delete(route('admin.rooms.destroy', $room))->assertSessionHas('error');

        $this->assertModelExists($room);
    }
}
