<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Pizza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Gestion du menu par l'administrateur. */
class AdminPizzaTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_menu_is_listed_with_pagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Pizza::create(['name' => "Spéciale $i", 'price' => 10]);
        }

        $this->actingAs($this->admin())->get(route('admin.pizza.index'))->assertOk()->assertSee('Calzone');
        $this->assertCount(1, $this->actingAs($this->admin())->get(route('admin.pizza.index', ['page' => 2]))->viewData('pizzas'));
    }

    public function test_admin_adds_a_pizza(): void
    {
        $this->actingAs($this->admin())->get(route('admin.pizza.create'))->assertOk();
        $this->actingAs($this->admin())
            ->post(route('admin.pizza.store'), ['name' => 'Napolitaine', 'price' => '10.90', 'description' => 'Tomate, anchois, câpres'])
            ->assertRedirect(route('admin.pizza.index'))
            ->assertSessionHas('success', 'Pizza ajoutée.');

        $this->assertEquals(10.9, Pizza::where('name', 'Napolitaine')->value('price'));
        $this->get('/pizzeria')->assertSee('Napolitaine');
    }

    public function test_pizza_is_validated(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.pizza.store'), ['name' => 'Margherita', 'price' => 9])->assertSessionHasErrors('name');
        $this->actingAs($admin)->post(route('admin.pizza.store'), ['name' => 'Gratuite', 'price' => -1])->assertSessionHasErrors('price');
        $this->actingAs($admin)->post(route('admin.pizza.store'), ['name' => 'Sans prix', 'price' => 'abc'])->assertSessionHasErrors('price');

        $this->assertSame(6, Pizza::count());
    }

    public function test_admin_edits_a_pizza(): void
    {
        $pizza = Pizza::where('name', 'Reine')->firstOrFail();

        $this->actingAs($this->admin())->get(route('admin.pizza.edit', $pizza->id))->assertOk()->assertSee('Reine');
        $this->actingAs($this->admin())
            ->put(route('admin.pizza.update', $pizza->id), ['name' => 'Reine', 'price' => 13, 'description' => 'Nouvelle recette'])
            ->assertSessionHas('success', 'Pizza modifiée.');

        $this->assertEquals(13, $pizza->fresh()->price);
        $this->assertSame('Nouvelle recette', $pizza->fresh()->description);
    }

    public function test_pizza_cannot_take_the_name_of_another_one(): void
    {
        $pizza = Pizza::where('name', 'Reine')->firstOrFail();

        $this->actingAs($this->admin())->put(route('admin.pizza.update', $pizza->id), ['name' => 'Calzone', 'price' => 12])
            ->assertSessionHasErrors('name');

        $this->assertSame('Reine', $pizza->fresh()->name);
    }

    public function test_deleted_pizza_stays_in_past_orders(): void
    {
        $customer = $this->customer();
        $groupId = $this->placeOrder($customer, ['Calzone' => 1]);
        $pizza = Pizza::where('name', 'Calzone')->firstOrFail();

        $this->actingAs($this->admin())->delete(route('admin.pizza.destroy', $pizza->id))->assertSessionHas('success', 'Pizza supprimée.');

        $this->assertNull($pizza->fresh());
        $this->assertSame('Calzone', Order::where('order_group_id', $groupId)->value('pizza_name'));
        $this->actingAs($customer)->get(route('customer.home', $customer->id))->assertSee('Calzone x1');
    }

    public function test_unknown_pizza_returns_404(): void
    {
        $this->actingAs($this->admin())->get(route('admin.pizza.edit', 999))->assertNotFound();
        $this->actingAs($this->admin())->delete(route('admin.pizza.destroy', 999))->assertNotFound();
    }
}
