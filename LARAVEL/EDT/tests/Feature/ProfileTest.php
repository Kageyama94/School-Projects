<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_the_account_information(): void
    {
        $user = User::factory()->create(['name' => 'Ada Lovelace']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee($user->identifiant)
            ->assertSee('Ada Lovelace');
    }

    public function test_profile_name_is_read_only_for_every_role(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role, 'name' => 'Ada Lovelace']);

            $this->actingAs($user)->get('/profile')->assertOk()->assertDontSee('name="name"', false);
            $this->actingAs($user)->patch('/profile', ['name' => 'Autre nom'])->assertStatus(405);
            $this->actingAs($user)->delete('/profile')->assertStatus(405);

            $this->assertSame('Ada Lovelace', $user->fresh()->name);
        }
    }

    public function test_profile_password_form_does_not_ask_for_the_current_password(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk()
            ->assertSee('name="password"', false)
            ->assertDontSee('current_password');
    }
}
