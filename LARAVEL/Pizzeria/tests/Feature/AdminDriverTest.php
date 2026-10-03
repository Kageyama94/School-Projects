<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Gestion des comptes livreurs par l'administrateur. */
class AdminDriverTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_admin_creates_a_driver_who_can_log_in(): void
    {
        $this->actingAs($this->admin())->get(route('admin.driver.create'))->assertOk();
        $this->actingAs($this->admin())
            ->post(route('admin.driver.store'), ['name' => 'Paul Martin', 'username' => 'paul', 'password' => 'secret', 'password_confirmation' => 'secret'])
            ->assertRedirect(route('admin.driver.index'))
            ->assertSessionHas('success', 'Livreur créé.');

        $user = User::where('name', 'paul')->firstOrFail();
        $this->assertSame('driver', $user->role);
        $this->assertSame('Paul Martin', Driver::where('user_id', $user->id)->value('name'));
        $this->actingAs($this->admin())->get(route('admin.driver.index'))->assertSee('Paul Martin');

        $this->post(route('logout'));
        $this->post(route('login'), ['name' => 'paul', 'password' => 'secret'])->assertRedirect(route('driver.home'));
    }

    public function test_driver_account_is_validated(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.driver.store'), ['name' => 'Paul', 'username' => 'admin', 'password' => 'secret', 'password_confirmation' => 'secret'])
            ->assertSessionHasErrors('username');
        $this->actingAs($admin)
            ->post(route('admin.driver.store'), ['name' => 'Paul', 'username' => 'paul', 'password' => 'abc', 'password_confirmation' => 'abc'])
            ->assertSessionHasErrors('password');
        $this->actingAs($admin)
            ->post(route('admin.driver.store'), ['name' => '', 'username' => 'paul', 'password' => 'secret', 'password_confirmation' => 'secret'])
            ->assertSessionHasErrors('name');

        $this->assertSame(0, Driver::count());
        $this->assertFalse(User::where('name', 'paul')->exists());
    }

    public function test_admin_renames_a_driver(): void
    {
        $driver = $this->driver('Paul');

        $this->actingAs($this->admin())->get(route('admin.driver.edit', $driver->id))->assertOk();
        $this->actingAs($this->admin())->put(route('admin.driver.update', $driver->id), ['name' => 'Paul Martin'])
            ->assertSessionHas('success', 'Livreur modifié.');

        $this->assertSame('Paul Martin', $driver->fresh()->name);
    }

    public function test_deleting_a_driver_removes_his_account(): void
    {
        $driver = $this->driver();
        $login = $driver->user->name;

        $this->actingAs($this->admin())->delete(route('admin.driver.destroy', $driver->id))->assertSessionHas('success', 'Livreur supprimé.');

        $this->assertNull($driver->fresh());
        $this->assertFalse(User::where('name', $login)->exists());
        $this->post(route('logout'));
        $this->post(route('login'), ['name' => $login, 'password' => 'secret'])->assertSessionHasErrors('name');
    }

    public function test_deleting_a_driver_puts_his_deliveries_back_in_preparation(): void
    {
        $driver = $this->driver('Paul Martin');
        $groupId = $this->orderInDelivery($driver);

        $this->actingAs($this->admin())->delete(route('admin.driver.destroy', $driver->id));

        $order = Order::where('order_group_id', $groupId)->first();
        $this->assertSame('preparing', $order->status);
        $this->assertNull($order->driver_id);
        $this->assertNull($order->driver_name, "la commande n'affiche plus de livreur");
    }

    public function test_delivered_orders_keep_the_name_of_a_deleted_driver(): void
    {
        $driver = $this->driver('Paul Martin');
        $groupId = $this->orderInDelivery($driver);
        $this->actingAs($driver->user)->post(route('driver.deliver', $groupId));

        $this->actingAs($this->admin())->delete(route('admin.driver.destroy', $driver->id));

        $order = Order::where('order_group_id', $groupId)->first();
        $this->assertSame('delivered', $order->status);
        $this->assertSame('Paul Martin', $order->driver_name, "l'historique garde le nom du livreur");
    }
}
