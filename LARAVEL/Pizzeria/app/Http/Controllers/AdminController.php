<?php

namespace App\Http\Controllers;

use App\Models\Pizza;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function admin() {
        return view('admin/home', [
            'pizzaCount' => Pizza::count(),
            'orderCount' => Order::count(),
            'driverCount' => Driver::count(),
            'pendingCount'=> Order::where('status', '!=', 'delivered')->count(),
        ]);
    }

    public function list() {
        return view('admin/pizza', ['pizzas' => Pizza::paginate(10)]);
    }

    public function add() {
        return view('admin/addPizza');
    }

    public function createPizza(Request $request) {
        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric|min:0',
        ]);

        Pizza::create($request->only(['name', 'price', 'description']));

        return redirect()->route('list')->with('success', 'Pizza ajoutée.');
    }

    public function edit($id) {
        return view('admin/editPizza', ['pizza' => Pizza::findOrFail($id)]);
    }

    public function update(Request $request, $id) {
        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric|min:0',
        ]);

        Pizza::findOrFail($id)->update($request->only(['name', 'price', 'description']));

        return redirect()->route('list')->with('success', 'Pizza modifiée.');
    }

    public function destroy($id) {
        Pizza::findOrFail($id)->delete();
        return redirect()->route('list')->with('success', 'Pizza supprimée.');
    }

    public function order() {
        $orders = Order::with(['customers', 'driver'])
            ->where('status', '!=', 'delivered')
            ->latest()
            ->get();
        $deliveredOrders = Order::with(['customers', 'driver'])
            ->where('status', 'delivered')
            ->latest()
            ->paginate(10, ['*'], 'delivered_page');
        $drivers = Driver::all();
        return view('admin/order', compact('orders', 'deliveredOrders', 'drivers'));
    }

    public function assignDriver(Request $request, $orderId) {
        $request->validate(['driver_id' => 'nullable|exists:drivers,id']);
        $order = Order::findOrFail($orderId);
        if ($order->status === 'delivered') {
            return back()->withErrors(['order' => 'Impossible de modifier une commande déjà livrée.']);
        }
        $driverId = $request->get('driver_id') ?: null;
        $order->update([
            'driver_id' => $driverId,
            'status' => $driverId ? 'delivering' : 'preparing',
        ]);
        $message = $driverId ? 'Livreur assigné.' : 'Livreur retiré.';
        return back()->with('success', $message);
    }

    public function delivery() {
        return view('admin/delivery_driver', ['drivers' => Driver::with('user')->get()]);
    }

    public function addDriver() {
        return view('admin/addDriver');
    }

    public function createDriver(Request $request) {
        $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users,name',
            'password' => 'required|min:4|confirmed',
        ]);

        $user = User::create([
            'name' => $request->get('username'),
            'password' => $request->get('password'),
            'role' => 'driver',
        ]);

        Driver::create(['name' => $request->get('name'), 'user_id' => $user->id]);

        return redirect()->route('delivery')->with('success', 'Livreur créé.');
    }

    public function editDriver($id) {
        return view('admin/editDeliveryDriver', ['driver' => Driver::with('user')->findOrFail($id)]);
    }

    public function updateDriver(Request $request, $id) {
        $request->validate(['name' => 'required']);

        Driver::findOrFail($id)->update(['name' => $request->get('name')]);

        return redirect()->route('delivery')->with('success', 'Livreur modifié.');
    }

    public function destroyDriver($id) {
        $driver = Driver::findOrFail($id);
        $userId = $driver->user_id;
        $driver->delete();
        if ($userId) {
            User::findOrFail($userId)->delete();
        }
        return redirect()->route('delivery')->with('success', 'Livreur supprimé.');
    }
}
