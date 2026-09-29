<?php

namespace Tests\Feature\Auth;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated_without_giving_the_current_one(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'password' => 'new-password1',
                'password_confirmation' => 'new-password1',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('new-password1', $user->refresh()->password));
    }

    public function test_changing_the_password_signs_out_the_other_devices_only(): void
    {
        $user = User::factory()->create(['remember_token' => 'ancien-jeton']);
        $other = User::factory()->create();
        $current = Str::random(40);
        foreach ([[$user->id, $current], [$user->id, 'autre-appareil'], [$other->id, 'autre-personne']] as [$userId, $id]) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => '', 'last_activity' => time()]);
        }

        $this->actingAs($user)
            ->withCookie(config('session.cookie'), $current)
            ->from('/profile')
            ->put('/password', [
                'password' => 'new-password1',
                'password_confirmation' => 'new-password1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sessions', ['id' => $current]);
        $this->assertDatabaseMissing('sessions', ['id' => 'autre-appareil']);
        $this->assertDatabaseHas('sessions', ['id' => 'autre-personne']);
        $this->assertNotSame('ancien-jeton', $user->refresh()->remember_token);
    }

    public function test_the_current_device_keeps_remember_me_after_a_password_change(): void
    {
        $user = User::factory()->create(['remember_token' => 'ancien-jeton']);
        $recaller = Auth::guard()->getRecallerName();

        $response = $this->actingAs($user)
            ->withCookie($recaller, "{$user->id}|ancien-jeton|{$user->password}")
            ->from('/profile')
            ->put('/password', [
                'password' => 'new-password1',
                'password_confirmation' => 'new-password1',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotSame('ancien-jeton', $user->remember_token);
        // Le cookie renvoyé porte le nouveau jeton : l'appareil reste mémorisé.
        $this->assertStringContainsString("|{$user->remember_token}|", $response->getCookie($recaller)->getValue());
    }

    public function test_no_remember_me_cookie_is_created_when_there_was_none(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'password' => 'new-password1',
                'password_confirmation' => 'new-password1',
            ])
            ->assertCookieMissing(Auth::guard()->getRecallerName());
    }

    public function test_password_cannot_be_updated_to_the_current_or_initial_one(): void
    {
        $user = User::factory()->teacher()->create();
        Teacher::factory()->create(['user_id' => $user->id, 'first_name' => 'Jean', 'last_name' => 'Dupont']);

        $this->actingAs($user)->put('/password', [
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->actingAs($user)->put('/password', [
            'password' => 'dupont_jean',
            'password_confirmation' => 'dupont_jean',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_password_must_contain_letters_and_numbers_and_be_long_enough(): void
    {
        $user = User::factory()->create();

        foreach (['abcdefgh', '12345678', 'abc1'] as $weak) {
            $this->actingAs($user)->put('/password', [
                'password' => $weak,
                'password_confirmation' => $weak,
            ])->assertSessionHasErrorsIn('updatePassword', 'password');
        }

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_a_forged_array_password_is_rejected_without_crashing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/password', [
            'password' => ['x'],
            'password_confirmation' => ['x'],
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_success_message_is_flashed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/password', [
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ])->assertSessionHas('success');
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/password', [
            'password' => 'new-password1',
            'password_confirmation' => 'other-password1',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }
}
