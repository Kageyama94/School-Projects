<?php

namespace Tests\Feature;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Inscription, connexion, profil et mot de passe oublié. */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_user_can_register(): void
    {
        $this->post('/inscription', [
            'name' => 'Léa', 'email' => 'lea@example.com',
            'password' => 'motdepasse', 'password_confirmation' => 'motdepasse',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_validation_messages_use_french_field_names(): void
    {
        $this->post('/inscription', ['email' => 'x@example.com'])
            ->assertSessionHasErrors(['name' => 'Le champ nom est obligatoire.']);
    }

    public function test_emails_are_case_insensitive(): void
    {
        $this->post('/inscription', [
            'name' => 'Léa', 'email' => '  Lea@Example.COM ',
            'password' => 'motdepasse', 'password_confirmation' => 'motdepasse',
        ]);
        $this->assertDatabaseHas('users', ['email' => 'lea@example.com']);
        $this->post('/deconnexion');

        $this->post('/inscription', [
            'name' => 'Doublon', 'email' => 'LEA@example.com',
            'password' => 'motdepasse', 'password_confirmation' => 'motdepasse',
        ])->assertSessionHasErrors('email');

        $this->post('/connexion', ['email' => 'ADMIN@JO.test', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_profile_can_be_updated(): void
    {
        $user = $this->spectator();

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee($user->email);
        $this->actingAs($user)->put(route('profile.update'), ['name' => 'Camille B.', 'email' => 'Camille@Example.com', 'email_password' => 'password'])
            ->assertSessionHasNoErrors();

        $this->assertSame(['Camille B.', 'camille@example.com'], [$user->fresh()->name, $user->fresh()->email]);

        $this->actingAs($user)->put(route('profile.update'), ['name' => 'X', 'email' => 'admin@jo.test'])
            ->assertSessionHasErrors('email');
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = $this->spectator();

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'mauvais', 'password' => 'nouveaumdp', 'password_confirmation' => 'nouveaumdp',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'password', 'password' => 'nouveaumdp', 'password_confirmation' => 'nouveaumdp',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nouveaumdp', $user->fresh()->password));
    }

    public function test_forgotten_password_can_be_reset(): void
    {
        Notification::fake();
        $user = $this->spectator();

        $this->post(route('password.email'), ['email' => 'Spectateur@jo.test'])->assertSessionHas('success');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();
        $this->post(route('password.update'), [
            'token' => $token, 'email' => $user->email,
            'password' => 'nouveaumdp', 'password_confirmation' => 'nouveaumdp',
        ])->assertRedirect(route('login'));

        $this->post('/connexion', ['email' => $user->email, 'password' => 'nouveaumdp'])->assertRedirect(route('home'));
    }

    public function test_forgot_password_does_not_reveal_unknown_emails(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'inconnu@example.com'])
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_reset_email_is_in_french(): void
    {
        $mail = (new ResetPassword('jeton'))->toMail($this->spectator());

        $this->assertSame('Réinitialisez votre mot de passe', $mail->subject);
        $this->assertSame('Réinitialiser le mot de passe', $mail->actionText);
    }
}
