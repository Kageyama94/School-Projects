<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    public function home() {
        $driver = Driver::where('user_id', Auth::id())->first();

        $orders = $driver
            ? Order::with('customers')
                ->where('driver_id', $driver->id)
                ->where('status', '!=', 'delivered')
                ->get()
            : collect();

        $history = $driver
            ? Order::with('customers')
                ->where('driver_id', $driver->id)
                ->where('status', 'delivered')
                ->latest()
                ->get()
            : collect();

        return view('driver/home', compact('orders', 'history'));
    }

    public function deliver($orderId) {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();
        $order = Order::where('id', $orderId)
                      ->where('driver_id', $driver->id)
                      ->firstOrFail();

        $order->update(['status' => 'delivered']);
        return back()->with('success', 'Commande marquée comme livrée.');
    }
}
