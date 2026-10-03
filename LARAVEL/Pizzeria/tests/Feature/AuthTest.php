<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Inscription, connexion (redirection selon le rôle) et déconnexion. */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_visitor_can_register_as_customer(): void
    {
        $this->post(route('register'), ['name' => 'marie', 'password' => 'secret', 'password_confirmation' => 'secret'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $user = User::where('name', 'marie')->firstOrFail();
        $this->assertSame('customer', $user->role, "l'inscription ne crée que des comptes client");
        $this->assertTrue(Hash::check('secret', $user->password), 'le mot de passe est haché');
    }

    public function test_registration_is_validated(): void
    {
        $this->post(route('register'), ['name' => 'admin', 'password' => 'secret', 'password_confirmation' => 'secret'])
            ->assertSessionHasErrors('name');
        $this->post(route('register'), ['name' => 'marie', 'password' => 'abc', 'password_confirmation' => 'abc'])
            ->assertSessionHasErrors('password');
        $this->post(route('register'), ['name' => 'marie', 'password' => 'secret', 'password_confirmation' => 'autre'])
            ->assertSessionHasErrors('password');

        $this->assertFalse(User::where('name', 'marie')->exists());
    }

    public function test_role_cannot_be_chosen_at_registration(): void
    {
        $this->post(route('register'), ['name' => 'pirate', 'password' => 'secret', 'password_confirmation' => 'secret', 'role' => 'admin']);

        $this->assertSame('customer', User::where('name', 'pirate')->value('role'));
    }

    public function test_login_redirects_according_to_role(): void
    {
        $customer = $this->customer();
        $driver = $this->driver();

        $this->post(route('login'), ['name' => 'admin', 'password' => 'admin'])->assertRedirect(route('admin.home'));
        $this->post(route('logout'));
        $this->post(route('login'), ['name' => $driver->user->name, 'password' => 'secret'])->assertRedirect(route('driver.home'));
        $this->post(route('logout'));
        $this->post(route('login'), ['name' => $customer->name, 'password' => 'secret'])->assertRedirect(route('customer.home', $customer->id));
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->post(route('login'), ['name' => 'admin', 'password' => 'mauvais'])->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['name' => 'admin', 'password' => 'mauvais'])->assertSessionHasErrors('name');
        }

        $this->post(route('login'), ['name' => 'admin', 'password' => 'admin'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_logged_in_user_is_sent_to_his_space_instead_of_login_page(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->get(route('login'))->assertRedirect(route('customer.home', $customer->id));
        $this->actingAs($customer)->get(route('register'))->assertRedirect(route('customer.home', $customer->id));
    }

    public function test_logout(): void
    {
        $this->actingAs($this->customer())->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
