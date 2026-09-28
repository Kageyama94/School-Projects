<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'identifiant' => $user->identifiant,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'identifiant' => $user->identifiant,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_login_is_locked_after_five_failed_attempts(): void
    {
        Event::fake([Lockout::class]);
        User::factory()->create(['identifiant' => '12345678']);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['identifiant' => '12345678', 'password' => 'mauvais-mot-de-passe'])
                ->assertSessionHasErrors('identifiant');
        }

        Event::assertNotDispatched(Lockout::class);

        // Le sixième essai est refusé, même avec le bon mot de passe.
        $this->post('/login', ['identifiant' => '12345678', 'password' => 'password'])
            ->assertSessionHasErrors('identifiant');

        $this->assertGuest();
        $this->assertStringContainsString('Trop de tentatives de connexion', session('errors')->first('identifiant'));
        Event::assertDispatched(Lockout::class);
    }

    public function test_the_lockout_only_concerns_the_targeted_identifiant(): void
    {
        User::factory()->create(['identifiant' => '11111111']);
        $other = User::factory()->create(['identifiant' => '22222222']);

        foreach (range(1, 6) as $attempt) {
            $this->post('/login', ['identifiant' => '11111111', 'password' => 'mauvais-mot-de-passe']);
        }

        $this->post('/login', ['identifiant' => $other->identifiant, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_a_successful_login_resets_the_failed_attempts(): void
    {
        User::factory()->create(['identifiant' => '12345678']);

        foreach (range(1, 4) as $attempt) {
            $this->post('/login', ['identifiant' => '12345678', 'password' => 'mauvais-mot-de-passe']);
        }

        $this->post('/login', ['identifiant' => '12345678', 'password' => 'password'])->assertRedirect();
        $this->post('/logout');

        foreach (range(1, 4) as $attempt) {
            $this->post('/login', ['identifiant' => '12345678', 'password' => 'mauvais-mot-de-passe']);
        }

        $this->post('/login', ['identifiant' => '12345678', 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_the_wrong_identifiant_message_is_in_french(): void
    {
        $this->post('/login', ['identifiant' => '99999999', 'password' => 'quelconque'])
            ->assertSessionHasErrors(['identifiant' => 'Ces identifiants ne correspondent à aucun compte connu.']);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
