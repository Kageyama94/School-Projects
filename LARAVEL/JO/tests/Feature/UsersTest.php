<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Comptes : gestion par l'admin, suppression par l'utilisateur, limites anti-abus, erreurs de formulaire. */
class UsersTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    // ---------------------------------------------------------------- Administration des comptes

    public function test_admin_can_promote_and_demote_users(): void
    {
        $user = $this->spectator();

        $this->actingAs($this->admin())->get(route('admin.users.index'))->assertOk()->assertSee($user->email);
        $this->actingAs($this->admin())->put(route('admin.users.update', $user), ['role' => 'admin'])->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->isAdmin());

        $this->actingAs($this->admin())->put(route('admin.users.update', $user), ['role' => 'spectator']);
        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_admin_cannot_change_own_role_or_delete_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), ['role' => 'spectator'])->assertSessionHasErrors('user');
        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_admin_can_delete_a_user_and_their_upcoming_tickets_are_cancelled(): void
    {
        $user = $this->spectator();
        $upcoming = $user->tickets()->active()->whereHas('event', fn ($q) => $q->upcoming())->first();

        $this->actingAs($this->admin())->delete(route('admin.users.destroy', $user))->assertSessionHas('success');

        $this->assertModelMissing($user);
        $this->assertNull($upcoming->fresh()->user_id, 'le billet reste, sans propriétaire');
        $this->assertNotNull($upcoming->fresh()->cancelled_at, 'la place à venir est libérée');
    }

    public function test_deleting_an_account_keeps_the_sales_history(): void
    {
        $user = $this->spectator();
        $past = Event::where('starts_at', '<', now())->whereNull('cancelled_at')->first();
        $pastTicket = $user->tickets()->create(['event_id' => $past->id, 'quantity' => 2, 'unit_price' => 100]);
        $revenue = fn () => $this->actingAs($this->admin())->get(route('admin.dashboard'))->viewData('revenue');
        $upcomingTotal = $user->tickets()->active()->whereHas('event', fn ($q) => $q->upcoming())->get()->sum(fn ($t) => $t->total());
        $before = $revenue();

        $this->actingAs($user)->delete(route('profile.destroy'), ['delete_password' => 'password']);

        // Seuls les billets à venir, annulés avec le compte, sortent des recettes.
        $this->assertSame($before - $upcomingTotal, $revenue());
        $this->assertNull($pastTicket->fresh()->cancelled_at);
        $this->assertNull($pastTicket->fresh()->user_id);
    }

    public function test_users_page_can_be_searched(): void
    {
        $this->actingAs($this->admin())->get(route('admin.users.index', ['q' => 'spectateur']))
            ->assertOk()->assertViewHas('users', fn ($p) => $p->total() === 1);
    }

    // ---------------------------------------------------------------- Suppression de son propre compte

    public function test_user_can_delete_own_account(): void
    {
        $user = $this->spectator();

        $this->actingAs($user)->delete(route('profile.destroy'), ['delete_password' => 'mauvais'])
            ->assertSessionHasErrors('delete_password');
        $this->assertModelExists($user);

        $this->actingAs($user)->delete(route('profile.destroy'), ['delete_password' => 'password'])
            ->assertRedirect(route('home'));
        $this->assertModelMissing($user);
        $this->assertSame(0, Ticket::where('user_id', $user->id)->count());
        $this->assertGuest();
    }

    public function test_sole_admin_cannot_delete_own_account(): void
    {
        $this->actingAs($this->admin())->delete(route('profile.destroy'), ['delete_password' => 'password'])
            ->assertSessionHasErrors('delete_password');
        $this->assertModelExists($this->admin());
    }

    // ---------------------------------------------------------------- Limites anti-abus

    public function test_rate_limit_is_per_form_and_per_email(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/connexion', ['email' => 'x@example.com', 'password' => 'faux']);
        }
        $this->post('/mot-de-passe-oublie', ['email' => 'x@example.com']);
        $this->post('/inscription', ['name' => 'A', 'email' => 'a@example.com', 'password' => 'motdepasse', 'password_confirmation' => 'motdepasse']);
        $this->post('/deconnexion');

        $this->post('/inscription', ['name' => 'B', 'email' => 'b@example.com', 'password' => 'motdepasse', 'password_confirmation' => 'motdepasse'])
            ->assertRedirect(route('home'));
    }

    public function test_repeated_failed_logins_show_a_french_message_on_the_form(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/connexion', ['email' => 'spectateur@jo.test', 'password' => 'faux']);
        }

        $this->from('/connexion')->followingRedirects()
            ->post('/connexion', ['email' => 'spectateur@jo.test', 'password' => 'password'])
            ->assertOk()
            ->assertSee('Trop de tentatives. Veuillez réessayer');
        $this->assertGuest();
    }

    // ---------------------------------------------------------------- Erreurs sous les champs

    public function test_field_errors_are_shown_under_the_field_only_once(): void
    {
        $html = $this->from('/inscription')->followingRedirects()
            ->post('/inscription', ['name' => '', 'email' => 'pas-un-email', 'password' => 'court', 'password_confirmation' => 'court'])
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Le champ nom est obligatoire.'));
        $this->assertStringContainsString('<span class="field-error" role="alert">Le champ nom est obligatoire.</span>', $html);
        $this->assertStringContainsString('voir les champs signalés', $html);
    }

    public function test_errors_without_a_field_stay_at_the_top(): void
    {
        $event = Event::has('tickets')->first();

        $html = $this->actingAs($this->admin())->from(route('admin.events.index'))->followingRedirects()
            ->delete(route('admin.events.destroy', $event))->getContent();

        $this->assertStringContainsString('Impossible de supprimer', $html);
        $this->assertStringNotContainsString('voir les champs signalés', $html);
    }
}
