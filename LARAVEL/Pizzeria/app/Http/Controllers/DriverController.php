<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    public function home() {
        $driver = Driver::where('user_id', Auth::id())->first();

        $activeGroups  = collect();
        $historyGroups = collect();
        $historyPage   = null;

        if ($driver) {
            $activeGroups = Order::with('customers')
                ->where('driver_id', $driver->id)
                ->where('status', 'delivering')
                ->whereNotNull('order_group_id')
                ->latest()
                ->get()
                ->groupBy('order_group_id');

            $historyPage = Order::where('driver_id', $driver->id)
                ->where('status', 'delivered')
                ->whereNotNull('order_group_id')
                ->selectRaw('order_group_id, MAX(created_at) as latest_at')
                ->groupBy('order_group_id')
                ->orderByDesc('latest_at')
                ->paginate(10);

            $historyOrders = Order::with('customers')
                ->whereIn('order_group_id', $historyPage->pluck('order_group_id'))
                ->get()
                ->groupBy('order_group_id');
            $historyGroups = $historyPage->pluck('order_group_id')
                ->mapWithKeys(fn($groupId) => [$groupId => $historyOrders->get($groupId, collect())]);
        }

        return view('driver/home', compact('activeGroups', 'historyGroups', 'historyPage'));
    }

    public function deliver($groupId) {
        $driver = Driver::where('user_id', Auth::id())->firstOrFail();
        $affected = Order::where('order_group_id', $groupId)
                         ->where('driver_id', $driver->id)
                         ->update(['status' => 'delivered', 'delivered_at' => now()]);
        abort_if($affected === 0, 404);
        return back()->with('success', 'Commande marquée comme livrée.');
    }
}
