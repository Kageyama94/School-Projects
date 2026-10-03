<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Pizza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Commande, récapitulatif, historique, annulation et profil côté client. */
class CustomerOrderTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_order_creates_one_line_per_pizza_in_a_single_group(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->post(route('order.store', $customer->id), $this->orderForm($customer, ['Margherita' => 2, 'Reine' => 1, 'Calzone' => 0]))
            ->assertRedirect(route('order.confirm', $customer->id));

        $orders = Order::all();
        $this->assertCount(2, $orders, 'une pizza en quantité 0 est ignorée');
        $this->assertCount(1, $orders->pluck('order_group_id')->unique());
        $this->assertSame(['pending'], $orders->pluck('status')->unique()->values()->all());
        $this->assertSame(30.5, $orders->sum('line_total'));

        $profile = Customer::where('user_id', $customer->id)->firstOrFail();
        $this->assertSame('Dupont', $profile->last_name);
        $this->assertCount(2, $profile->orders, 'les lignes sont rattachées au client');
    }

    public function test_confirmation_page_shows_the_summary(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->followingRedirects()
            ->post(route('order.store', $customer->id), $this->orderForm($customer, ['Margherita' => 2, 'Reine' => 1]))
            ->assertSee('Commande confirmée')
            ->assertSee('Merci pour votre commande')
            ->assertSee('12 rue de la Paix, Créteil')
            ->assertSee('19,00 €')
            ->assertSee('30,50 €');
    }

    public function test_confirmation_page_without_order_goes_back_home(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->get(route('order.confirm', $customer->id))->assertRedirect(route('customer.home', $customer->id));
    }

    public function test_order_needs_at_least_one_pizza(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->post(route('order.store', $customer->id), $this->orderForm($customer, ['Margherita' => 0]))
            ->assertSessionHasErrors(['pizza' => 'Veuillez sélectionner au moins une pizza.']);

        $this->assertSame(0, Order::count());
    }

    public function test_order_form_is_validated(): void
    {
        $customer = $this->customer();
        $post = fn (array $overrides) => $this->actingAs($customer)
            ->post(route('order.store', $customer->id), $this->orderForm($customer, overrides: $overrides));

        $post(['postal_code' => '940'])->assertSessionHasErrors('postal_code');
        $post(['email' => 'pas-un-email'])->assertSessionHasErrors('email');
        $post(['address' => ''])->assertSessionHasErrors('address');
        $post(['pizza' => [Pizza::value('id') => 100]])->assertSessionHasErrors('pizza.*');

        $this->assertSame(0, Order::count());
    }

    public function test_email_of_another_customer_is_refused(): void
    {
        $alice = $this->customer('alice');
        $bob = $this->customer('bob');
        $this->placeOrder($alice);

        $this->actingAs($bob)
            ->post(route('order.store', $bob->id), $this->orderForm($bob, overrides: ['email' => 'alice@example.com']))
            ->assertSessionHasErrors('email');

        // Le client peut en revanche recommander avec son propre e-mail.
        $this->actingAs($alice)
            ->post(route('order.store', $alice->id), $this->orderForm($alice))
            ->assertSessionHasNoErrors();
    }

    public function test_order_keeps_the_price_paid(): void
    {
        $customer = $this->customer();
        $groupId = $this->placeOrder($customer, ['Margherita' => 2]);

        Pizza::where('name', 'Margherita')->update(['price' => 15]);

        $this->assertEquals(9.5, Order::where('order_group_id', $groupId)->value('unit_price'));
        $this->actingAs($customer)->get(route('customer.home', $customer->id))->assertSee('19,00 €')->assertDontSee('30,00 €');
    }

    public function test_history_lists_the_customer_orders(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->get(route('customer.home', $customer->id))->assertSee("Vous n'avez pas encore passé de commande.", false);

        $this->placeOrder($customer, ['Pepperoni' => 3]);

        $this->actingAs($customer)->get(route('customer.home', $customer->id))
            ->assertSee('Bienvenue, Jean !')
            ->assertSee('Pepperoni x3')
            ->assertSee('33,00 €')
            ->assertSee('En attente');
    }

    public function test_customer_sees_only_his_own_orders(): void
    {
        $alice = $this->customer('alice');
        $bob = $this->customer('bob');
        $aliceGroup = $this->placeOrder($alice, ['Calzone' => 1]);

        $this->actingAs($bob)->get(route('customer.home', $bob->id))->assertDontSee('Calzone x1');
        $this->actingAs($bob)->get(route('order.show', [$bob->id, $aliceGroup]))->assertNotFound();
        $this->actingAs($bob)->delete(route('order.cancel', [$bob->id, $aliceGroup]))->assertNotFound();
        $this->actingAs($alice)->get(route('order.show', [$alice->id, $aliceGroup]))->assertOk()->assertSee('Calzone');
    }

    public function test_pending_order_can_be_cancelled(): void
    {
        $customer = $this->customer();
        $groupId = $this->placeOrder($customer);

        $this->actingAs($customer)->delete(route('order.cancel', [$customer->id, $groupId]))
            ->assertRedirect(route('customer.home', $customer->id))
            ->assertSessionHas('success', 'Commande annulée.');

        $this->assertSame(0, Order::count());
    }

    public function test_accepted_order_can_no_longer_be_cancelled(): void
    {
        $customer = $this->customer();
        $groupId = $this->placeOrder($customer);
        $this->actingAs($this->admin())->patch(route('admin.order.accept', $groupId));

        $this->actingAs($customer)->delete(route('order.cancel', [$customer->id, $groupId]))
            ->assertSessionHasErrors(['cancel' => 'Cette commande ne peut plus être annulée.']);

        $this->assertSame('preparing', $this->statusOf($groupId));
    }

    public function test_customer_can_update_his_profile(): void
    {
        $customer = $this->customer();
        $this->placeOrder($customer);

        $this->actingAs($customer)->put(route('customer.profile.update', $customer->id), [
            'first_name' => 'Jeanne', 'last_name' => 'Durand', 'email' => 'jeanne@example.com', 'phone' => '0699999999',
        ])->assertRedirect(route('customer.home', $customer->id))->assertSessionHas('success', 'Profil mis à jour.');

        $profile = Customer::where('user_id', $customer->id)->sole();
        $this->assertSame('Durand', $profile->last_name);
        $this->assertSame('jeanne@example.com', $profile->email);
    }

    public function test_profile_can_be_created_before_any_order(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->get(route('customer.profile.edit', $customer->id))->assertOk();
        $this->actingAs($customer)->put(route('customer.profile.update', $customer->id), [
            'first_name' => 'Jean', 'last_name' => 'Dupont', 'email' => 'jean@example.com', 'phone' => '0601020304',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Customer::where('user_id', $customer->id)->exists());
    }
}
