<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Connexion, sessions, profil, rôles et réservations concurrentes. */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    // ---------------------------------------------------------------- Limite de connexion

    public function test_successful_logins_are_not_limited(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->post('/connexion', ['email' => 'spectateur@jo.test', 'password' => 'password'])->assertRedirect(route('home'));
            $this->post('/deconnexion');
        }
    }

    public function test_failed_logins_are_limited_and_reset_after_success(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->post('/connexion', ['email' => 'spectateur@jo.test', 'password' => 'faux']);
        }
        $this->post('/connexion', ['email' => 'spectateur@jo.test', 'password' => 'password'])->assertRedirect(route('home'));
        $this->post('/deconnexion');

        // Le succès a remis le compteur à zéro : 4 nouveaux échecs ne bloquent pas encore.
        for ($i = 1; $i <= 4; $i++) {
            $this->post('/connexion', ['email' => 'spectateur@jo.test', 'password' => 'faux']);
        }
        $this->post('/connexion', ['email' => 'spectateur@jo.test', 'password' => 'password'])->assertRedirect(route('home'));
    }

    // ---------------------------------------------------------------- Sessions

    public function test_password_change_elsewhere_logs_this_session_out(): void
    {
        $user = $this->spectator();
        $this->actingAs($user)->get('/profil')->assertOk();

        // Le mot de passe change depuis un autre appareil (ou une réinitialisation) : cette session est fermée.
        $user->forceFill(['password' => 'un-autre-mot-de-passe'])->save();

        $this->get('/profil')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_changing_own_password_keeps_the_current_session(): void
    {
        $user = $this->spectator();
        $this->actingAs($user)->get('/profil');

        $this->put(route('profile.password'), [
            'current_password' => 'password', 'password' => 'nouveaumdp', 'password_confirmation' => 'nouveaumdp',
        ])->assertSessionHasNoErrors();

        $this->get('/profil')->assertOk();
    }

    // ---------------------------------------------------------------- Profil

    public function test_changing_email_requires_the_password(): void
    {
        $user = $this->spectator();

        $this->actingAs($user)->put(route('profile.update'), ['name' => $user->name, 'email' => 'pirate@example.com'])
            ->assertSessionHasErrors(['email_password' => "Saisissez votre mot de passe pour changer d'adresse e-mail."]);
        $this->actingAs($user)->put(route('profile.update'), ['name' => $user->name, 'email' => 'pirate@example.com', 'email_password' => 'faux'])
            ->assertSessionHasErrors('email_password');
        $this->assertSame('spectateur@jo.test', $user->fresh()->email);

        $this->actingAs($user)->put(route('profile.update'), ['name' => $user->name, 'email' => 'nouveau@example.com', 'email_password' => 'password'])
            ->assertSessionHasNoErrors();
        $this->assertSame('nouveau@example.com', $user->fresh()->email);
    }

    public function test_changing_only_the_name_needs_no_password(): void
    {
        $user = $this->spectator();

        $this->actingAs($user)->put(route('profile.update'), ['name' => 'Camille B.', 'email' => $user->email])->assertSessionHasNoErrors();
        $this->assertSame('Camille B.', $user->fresh()->name);
    }

    /** Un champ envoyé comme liste (email[]=…) est refusé par la validation, sans erreur 500. */
    public function test_malformed_fields_are_rejected_without_server_error(): void
    {
        $email = ['email' => ['a@example.com']];

        $this->post('/inscription', [...$email, 'name' => 'X', 'password' => 'motdepasse', 'password_confirmation' => 'motdepasse'])
            ->assertSessionHasErrors('email');
        $this->post('/connexion', [...$email, 'password' => 'x'])->assertSessionHasErrors('email');
        $this->post('/mot-de-passe-oublie', $email)->assertSessionHasErrors('email');
        $this->get('/reinitialiser-mot-de-passe/jeton?email[]=a@example.com')->assertOk();
        $this->actingAs($this->spectator())->put(route('profile.update'), [...$email, 'name' => 'X'])->assertSessionHasErrors('email');

        $this->actingAs($this->admin())->post(route('admin.countries.store'), ['name' => 'X', 'code' => ['FRA'], 'iso' => 'fr'])
            ->assertSessionHasErrors('code');
    }

    // ---------------------------------------------------------------- Rôles

    public function test_role_change_asks_for_confirmation(): void
    {
        $this->actingAs($this->admin())->get(route('admin.users.index'))
            ->assertSee('Nommer Camille Spectatrice administrateur ?')
            ->assertSee('Enregistrer')
            ->assertDontSee('onchange', false);
    }

    // ---------------------------------------------------------------- Réservation concurrente

    public function test_booking_is_refused_if_the_event_is_cancelled_meanwhile(): void
    {
        $event = $this->upcomingEventWithoutTickets();

        // L'admin annule l'épreuve juste après que la page a chargé l'épreuve, avant l'écriture du billet.
        $cancelled = false;
        Event::retrieved(function (Event $e) use ($event, &$cancelled) {
            if (! $cancelled && $e->id === $event->id) {
                $cancelled = true;
                DB::table('events')->where('id', $e->id)->update(['cancelled_at' => now()]);
            }
        });

        $this->actingAs($this->spectator())->post(route('tickets.store', $event), ['quantity' => 1])
            ->assertSessionHasErrors(['quantity' => 'Cette épreuve a été annulée.']);
        $this->assertSame(0, $event->tickets()->count());
    }
}
