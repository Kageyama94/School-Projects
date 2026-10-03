<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Espace livreur : commandes à livrer, validation de la livraison et historique. */
class DriverTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_driver_sees_the_orders_assigned_to_him(): void
    {
        $paul = $this->driver('Paul');
        $lea = $this->driver('Léa');
        $this->orderInDelivery($paul, $this->customer('alice'));

        $this->actingAs($paul->user)->get(route('driver.home'))
            ->assertSee('Jean Dupont')
            ->assertSee('0601020304')
            ->assertSee('12 rue de la Paix, Créteil')
            ->assertSee('Margherita x2');
        $this->actingAs($lea->user)->get(route('driver.home'))->assertSee('Aucune commande en attente de livraison.');
    }

    public function test_driver_marks_an_order_as_delivered(): void
    {
        $driver = $this->driver();
        $groupId = $this->orderInDelivery($driver);

        $this->actingAs($driver->user)->post(route('driver.deliver', $groupId))->assertSessionHas('success', 'Commande marquée comme livrée.');

        $this->assertSame('delivered', $this->statusOf($groupId));
        $this->assertNotNull(Order::where('order_group_id', $groupId)->value('delivered_at'));
        $this->actingAs($driver->user)->get(route('driver.home'))
            ->assertSee('Livraisons effectuées (1)')
            ->assertSee('Aucune commande en attente de livraison.');
    }

    public function test_driver_cannot_deliver_an_order_of_another_driver(): void
    {
        $paul = $this->driver('Paul');
        $lea = $this->driver('Léa');
        $groupId = $this->orderInDelivery($paul);

        $this->actingAs($lea->user)->post(route('driver.deliver', $groupId))->assertNotFound();

        $this->assertSame('delivering', $this->statusOf($groupId));
    }

    public function test_delivery_date_is_not_overwritten_by_a_second_validation(): void
    {
        $driver = $this->driver();
        $groupId = $this->orderInDelivery($driver);
        $this->actingAs($driver->user)->post(route('driver.deliver', $groupId));
        $deliveredAt = Order::where('order_group_id', $groupId)->value('delivered_at');

        $this->travel(2)->hours();
        $this->actingAs($driver->user)->post(route('driver.deliver', $groupId))->assertNotFound();

        $this->assertEquals($deliveredAt, Order::where('order_group_id', $groupId)->value('delivered_at'));
    }

    public function test_customer_sees_the_delivery(): void
    {
        $customer = $this->customer();
        $driver = $this->driver('Paul Martin');
        $groupId = $this->orderInDelivery($driver, $customer);
        $this->actingAs($driver->user)->post(route('driver.deliver', $groupId));

        $this->actingAs($customer)->get(route('customer.home', $customer->id))->assertSee('Livrée');
        $this->actingAs($customer)->get(route('order.show', [$customer->id, $groupId]))->assertSee('Paul Martin');
    }
}
