<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Suivi des commandes par l'administrateur : acceptation, assignation d'un livreur, filtres. */
class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_admin_accepts_a_pending_order(): void
    {
        $groupId = $this->placeOrder($this->customer());

        $this->actingAs($this->admin())->patch(route('admin.order.accept', $groupId))->assertSessionHas('success', 'Commande acceptée.');

        $this->assertSame('preparing', $this->statusOf($groupId));
    }

    public function test_only_pending_orders_can_be_accepted(): void
    {
        $groupId = $this->orderInDelivery($this->driver());

        $this->actingAs($this->admin())->patch(route('admin.order.accept', $groupId))->assertNotFound();
        $this->actingAs($this->admin())->patch(route('admin.order.accept', 'inconnu'))->assertNotFound();
        $this->assertSame('delivering', $this->statusOf($groupId));
    }

    public function test_driver_can_only_be_assigned_after_acceptance(): void
    {
        $driver = $this->driver();
        $groupId = $this->placeOrder($this->customer());

        $this->actingAs($this->admin())->patch(route('admin.order.assign', $groupId), ['driver_id' => $driver->id])
            ->assertSessionHasErrors('order');

        $this->assertSame('pending', $this->statusOf($groupId));
        $this->assertNull(Order::where('order_group_id', $groupId)->value('driver_id'));
    }

    public function test_assigning_a_driver_puts_every_line_in_delivery(): void
    {
        $driver = $this->driver('Paul Martin');
        $groupId = $this->orderInDelivery($driver, null);

        $lines = Order::where('order_group_id', $groupId)->get();
        $this->assertSame(['delivering'], $lines->pluck('status')->unique()->values()->all());
        $this->assertSame([$driver->id], $lines->pluck('driver_id')->unique()->values()->all());
        $this->assertSame('Paul Martin', $lines->first()->driver_name);
    }

    public function test_unassigning_the_driver_puts_the_order_back_in_preparation(): void
    {
        $groupId = $this->orderInDelivery($this->driver());

        $this->actingAs($this->admin())->patch(route('admin.order.assign', $groupId), ['driver_id' => ''])
            ->assertSessionHas('success', 'Livreur retiré.');

        $order = Order::where('order_group_id', $groupId)->first();
        $this->assertSame('preparing', $order->status);
        $this->assertNull($order->driver_id);
        $this->assertNull($order->driver_name);
    }

    public function test_delivered_order_can_no_longer_be_reassigned(): void
    {
        $driver = $this->driver();
        $groupId = $this->orderInDelivery($driver);
        $this->actingAs($driver->user)->post(route('driver.deliver', $groupId));

        $this->actingAs($this->admin())->patch(route('admin.order.assign', $groupId), ['driver_id' => ''])
            ->assertSessionHasErrors('order');

        $this->assertSame('delivered', $this->statusOf($groupId));
    }

    public function test_unknown_driver_is_refused(): void
    {
        $groupId = $this->placeOrder($this->customer());
        $this->actingAs($this->admin())->patch(route('admin.order.accept', $groupId));

        $this->actingAs($this->admin())->patch(route('admin.order.assign', $groupId), ['driver_id' => 999])
            ->assertSessionHasErrors('driver_id');
    }

    public function test_orders_page_splits_orders_by_status(): void
    {
        $driver = $this->driver();
        $pending = $this->placeOrder($this->customer('alice'));
        $inDelivery = $this->orderInDelivery($driver, $this->customer('bob'));
        $delivered = $this->orderInDelivery($driver, $this->customer('chloe'));
        $this->actingAs($driver->user)->post(route('driver.deliver', $delivered));

        $response = $this->actingAs($this->admin())->get(route('admin.order.index'))->assertOk();

        $this->assertSame([$pending], $response->viewData('pendingGroups')->keys()->all());
        $this->assertSame([$inDelivery], $response->viewData('activeGroups')->keys()->all());
        $this->assertSame([$delivered], $response->viewData('deliveredGroups')->keys()->all());
    }

    public function test_orders_can_be_filtered_by_driver_and_date(): void
    {
        $paul = $this->driver('Paul');
        $lea = $this->driver('Léa');
        $forPaul = $this->orderInDelivery($paul, $this->customer('alice'));
        $this->orderInDelivery($lea, $this->customer('bob'));

        $byDriver = $this->actingAs($this->admin())->get(route('admin.order.index', ['driver_id' => $paul->id]));
        $this->assertSame([$forPaul], $byDriver->viewData('activeGroups')->keys()->all());

        $yesterday = $this->actingAs($this->admin())->get(route('admin.order.index', ['date' => now()->subDay()->toDateString()]));
        $this->assertTrue($yesterday->viewData('activeGroups')->isEmpty());

        $this->actingAs($this->admin())->get(route('admin.order.index', ['driver_id' => 999]))->assertSessionHasErrors('driver_id');
    }

    public function test_order_detail(): void
    {
        $groupId = $this->placeOrder($this->customer(), ['Quatre Fromages' => 1]);

        $this->actingAs($this->admin())->get(route('admin.order.show', $groupId))->assertOk()->assertSee('Quatre Fromages')->assertSee('Dupont');
        $this->actingAs($this->admin())->get(route('admin.order.show', 'inconnu'))->assertNotFound();
    }

    public function test_dashboard_counts_orders_by_group(): void
    {
        $driver = $this->driver();
        $this->placeOrder($this->customer('alice'), ['Margherita' => 1, 'Reine' => 2]);
        $delivered = $this->orderInDelivery($driver, $this->customer('bob'));
        $this->actingAs($driver->user)->post(route('driver.deliver', $delivered));

        $response = $this->actingAs($this->admin())->get(route('admin.home'))->assertOk();

        $this->assertSame(6, $response->viewData('pizzaCount'));
        $this->assertEquals(2, $response->viewData('orderCount'), 'une commande de plusieurs pizzas compte une seule fois');
        $this->assertEquals(1, $response->viewData('pendingCount'));
        $this->assertEquals(1, $response->viewData('deliveredCount'));
        $this->assertSame(1, $response->viewData('driverCount'));
    }
}
