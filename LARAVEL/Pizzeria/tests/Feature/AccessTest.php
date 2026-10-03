<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Cloisonnement des espaces client, livreur et administrateur. */
class AccessTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_home_page_lists_the_menu(): void
    {
        $this->get('/')->assertRedirect('/pizzeria');
        $this->get('/pizzeria')->assertOk()->assertSee('Margherita')->assertSee('9,50 €');
    }

    public function test_guest_is_sent_to_login(): void
    {
        $customer = $this->customer();

        $this->get(route('customer.home', $customer->id))->assertRedirect(route('login'));
        $this->get(route('driver.home'))->assertRedirect(route('login'));
        $this->get(route('admin.home'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_reach_driver_or_admin_space(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->get(route('driver.home'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.home'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.order.index'))->assertForbidden();
    }

    public function test_customer_cannot_reach_another_customer_space(): void
    {
        $alice = $this->customer('alice');
        $bob = $this->customer('bob');

        $this->actingAs($alice)->get(route('customer.home', $alice->id))->assertOk();
        $this->actingAs($alice)->get(route('customer.home', $bob->id))->assertForbidden();
        $this->actingAs($alice)->post(route('order.store', $bob->id), $this->orderForm($alice))->assertForbidden();
    }

    public function test_driver_cannot_reach_admin_or_customer_space(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver->user)->get(route('driver.home'))->assertOk();
        $this->actingAs($driver->user)->get(route('admin.home'))->assertForbidden();
        $this->actingAs($driver->user)->get(route('customer.home', $driver->user->id))->assertForbidden();
    }

    public function test_admin_cannot_use_driver_or_customer_space(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.home'))->assertOk();
        $this->actingAs($admin)->get(route('driver.home'))->assertForbidden();
        $this->actingAs($admin)->get(route('customer.home', $admin->id))->assertForbidden();
    }
}
