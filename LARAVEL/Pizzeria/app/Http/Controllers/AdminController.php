<?php

namespace App\Http\Controllers;

use App\Models\Pizza;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function admin() {
        $stats = Order::whereNotNull('order_group_id')
            ->selectRaw('status, COUNT(DISTINCT order_group_id) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin/home', [
            'pizzaCount' => Pizza::count(),
            'orderCount' => $stats->sum(),
            'pendingCount' => ($stats['pending'] ?? 0) + ($stats['preparing'] ?? 0) + ($stats['delivering'] ?? 0),
            'deliveredCount'=> $stats['delivered'] ?? 0,
            'driverCount' => Driver::count(),
        ]);
    }

    public function list() {
        return view('admin/pizza', ['pizzas' => Pizza::orderBy('name')->simplePaginate(10)]);
    }

    public function add() {
        return view('admin/addPizza');
    }

    public function createPizza(Request $request) {
        $request->validate([
            'name' => 'required|unique:pizzas,name',
            'price' => 'required|numeric|min:0',
        ]);

        Pizza::create($request->only(['name', 'price', 'description']));

        return redirect()->route('admin.pizza.index')->with('success', 'Pizza ajoutée.');
    }

    public function edit($id) {
        return view('admin/editPizza', ['pizza' => Pizza::findOrFail($id)]);
    }

    public function update(Request $request, $id) {
        $request->validate([
            'name' => ['required', Rule::unique('pizzas', 'name')->ignore($id)],
            'price' => 'required|numeric|min:0',
        ]);

        Pizza::findOrFail($id)->update($request->only(['name', 'price', 'description']));

        return redirect()->route('admin.pizza.index')->with('success', 'Pizza modifiée.');
    }

    public function destroy($id) {
        Pizza::findOrFail($id)->delete();
        return redirect()->route('admin.pizza.index')->with('success', 'Pizza supprimée.');
    }

    public function order(Request $request) {
        $drivers = Driver::all();

        $baseQuery = Order::whereNotNull('order_group_id');

        if ($request->filled('driver_id') && $drivers->contains('id', $request->driver_id)) {
            $baseQuery->where('driver_id', $request->driver_id);
        }

        if ($request->filled('date') && strtotime($request->date)) {
            $baseQuery->whereDate('created_at', $request->date);
        }

        $activeOrders  = (clone $baseQuery)->whereIn('status', ['pending', 'preparing', 'delivering'])->latest()->get();
        $pendingGroups = $activeOrders->where('status', 'pending')->groupBy('order_group_id');
        $activeGroups  = $activeOrders->whereIn('status', ['preparing', 'delivering'])->groupBy('order_group_id');

        $deliveredPage = (clone $baseQuery)->where('status', 'delivered')
            ->selectRaw('order_group_id, MAX(created_at) as latest_at')
            ->groupBy('order_group_id')
            ->orderByDesc('latest_at')
            ->paginate(10)
            ->withQueryString();

        $deliveredOrders = Order::whereIn('order_group_id', $deliveredPage->pluck('order_group_id'))->get()
            ->groupBy('order_group_id');
        $deliveredGroups = $deliveredPage->pluck('order_group_id')
            ->mapWithKeys(fn($groupId) => [$groupId => $deliveredOrders->get($groupId, collect())]);

        return view('admin/order', compact('pendingGroups', 'activeGroups', 'deliveredGroups', 'deliveredPage', 'drivers'));
    }

    public function acceptOrder($groupId) {
        $affected = Order::where('order_group_id', $groupId)->where('status', 'pending')->update(['status' => 'preparing']);
        abort_if($affected === 0, 404);
        return back()->with('success', 'Commande acceptée.');
    }

    public function orderDetailAdmin($groupId) {
        $orders = Order::with('customers')
            ->where('order_group_id', $groupId)
            ->get();
        abort_if($orders->isEmpty(), 404);
        return view('admin/orderDetail', compact('orders', 'groupId'));
    }

    public function assignDriver(Request $request, $groupId) {
        $request->validate(['driver_id' => 'nullable|exists:drivers,id']);
        $first = Order::where('order_group_id', $groupId)->firstOrFail();
        if ($first->status === 'pending') {
            return back()->withErrors(['order' => 'Acceptez la commande avant d\'assigner un livreur.']);
        }
        if ($first->status === 'delivered') {
            return back()->withErrors(['order' => 'Impossible de modifier une commande déjà livrée.']);
        }
        $driverId   = $request->get('driver_id') ?: null;
        $driverName = $driverId ? Driver::find($driverId)?->name : null;
        Order::where('order_group_id', $groupId)->update([
            'driver_id'   => $driverId,
            'driver_name' => $driverName,
            'status'      => $driverId ? 'delivering' : 'preparing',
        ]);
        return back()->with('success', $driverId ? 'Livreur assigné.' : 'Livreur retiré.');
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

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->get('username'),
                'password' => $request->get('password'),
                'role' => 'driver',
            ]);
            Driver::create(['name' => $request->get('name'), 'user_id' => $user->id]);
        });

        return redirect()->route('admin.driver.index')->with('success', 'Livreur créé.');
    }

    public function editDriver($id) {
        return view('admin/editDeliveryDriver', ['driver' => Driver::with('user')->findOrFail($id)]);
    }

    public function updateDriver(Request $request, $id) {
        $request->validate(['name' => 'required']);

        Driver::findOrFail($id)->update(['name' => $request->get('name')]);

        return redirect()->route('admin.driver.index')->with('success', 'Livreur modifié.');
    }

    public function destroyDriver($id) {
        $driver = Driver::findOrFail($id);
        $userId = $driver->user_id;
        Order::where('driver_id', $driver->id)->where('status', 'delivering')->update(['status' => 'preparing']);
        Order::where('driver_id', $driver->id)->update(['driver_id' => null]);
        $driver->delete();
        if ($userId) {
            User::find($userId)?->delete();
        }
        return redirect()->route('admin.driver.index')->with('success', 'Livreur supprimé.');
    }
}
