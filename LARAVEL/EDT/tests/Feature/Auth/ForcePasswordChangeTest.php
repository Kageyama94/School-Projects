<?php

namespace Tests\Feature\Auth;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private function teacherWhoMustChangePassword(string $currentPassword = 'dupont_jean'): User
    {
        $user = User::factory()->teacher()->create([
            'password' => $currentPassword,
            'must_change_password' => true,
        ]);
        Teacher::factory()->create(['user_id' => $user->id, 'first_name' => 'Jean', 'last_name' => 'Dupont']);

        return $user;
    }

    private function changePassword(User $user, string $password): TestResponse
    {
        return $this->actingAs($user)->put(route('password.force-change.update'), [
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    public function test_user_who_must_change_password_is_redirected_from_the_dashboard(): void
    {
        $user = $this->teacherWhoMustChangePassword();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('password.force-change'));
    }

    public function test_form_does_not_ask_for_the_current_password(): void
    {
        $user = $this->teacherWhoMustChangePassword();

        $this->actingAs($user)->get(route('password.force-change'))
            ->assertOk()
            ->assertDontSee('current_password')
            ->assertSee('name="password"', false);
    }

    public function test_password_can_be_changed_without_giving_the_current_one(): void
    {
        $user = $this->teacherWhoMustChangePassword();

        $this->changePassword($user, 'un-nouveau-mdp1')
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('un-nouveau-mdp1', $user->password));
    }

    public function test_new_password_cannot_be_the_initial_one(): void
    {
        $user = $this->teacherWhoMustChangePassword();

        $this->changePassword($user, 'dupont_jean')->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_new_password_cannot_be_the_initial_one_even_if_current_differs(): void
    {
        $user = $this->teacherWhoMustChangePassword('un-autre-mdp');

        $this->changePassword($user, 'dupont_jean')->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_new_password_cannot_be_the_current_one(): void
    {
        $user = $this->teacherWhoMustChangePassword('un-autre-mdp');

        $this->changePassword($user, 'un-autre-mdp')->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
    }
}
