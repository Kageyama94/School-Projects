<?php

namespace Tests;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Pizza;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Compte administrateur créé par le seeder (admin / admin). */
    protected function admin(): User
    {
        return User::where('name', 'admin')->firstOrFail();
    }

    protected function customer(string $name = 'client'): User
    {
        return User::create(['name' => $name, 'password' => 'secret', 'role' => 'customer']);
    }

    /** Livreur et son compte de connexion (identifiant unique généré, mot de passe "secret"). */
    protected function driver(string $name = 'Paul Martin'): Driver
    {
        $user = User::create(['name' => 'livreur'.(User::max('id') + 1), 'password' => 'secret', 'role' => 'driver']);

        return Driver::create(['user_id' => $user->id, 'name' => $name]);
    }

    /** Formulaire de commande valide ; $pizzas associe un nom de pizza du seeder à sa quantité. */
    protected function orderForm(User $customer, array $pizzas = ['Margherita' => 2], array $overrides = []): array
    {
        $quantities = [];
        foreach ($pizzas as $name => $quantity) {
            $quantities[Pizza::where('name', $name)->value('id')] = $quantity;
        }

        return array_merge([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => $customer->name.'@example.com',
            'phone' => '0601020304',
            'address' => '12 rue de la Paix, Créteil',
            'postal_code' => '94000',
            'pizza' => $quantities,
        ], $overrides);
    }

    /** Passe une commande au nom du client et renvoie son identifiant de groupe. */
    protected function placeOrder(User $customer, array $pizzas = ['Margherita' => 2]): string
    {
        $this->actingAs($customer)->post(route('order.store', $customer->id), $this->orderForm($customer, $pizzas));

        return Order::latest('id')->value('order_group_id');
    }

    /** Commande acceptée par l'admin puis confiée au livreur (statut "delivering"). */
    protected function orderInDelivery(Driver $driver, ?User $customer = null): string
    {
        $groupId = $this->placeOrder($customer ?? $this->customer());
        $this->actingAs($this->admin())->patch(route('admin.order.accept', $groupId));
        $this->actingAs($this->admin())->patch(route('admin.order.assign', $groupId), ['driver_id' => $driver->id]);

        return $groupId;
    }

    protected function statusOf(string $groupId): string
    {
        return Order::where('order_group_id', $groupId)->value('status');
    }
}
