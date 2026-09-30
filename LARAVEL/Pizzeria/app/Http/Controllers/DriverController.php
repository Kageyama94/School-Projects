<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    private function getDriver(): ?Driver {
        return Driver::where('user_id', Auth::id())->first();
    }

    public function home() {
        $driver = $this->getDriver();

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

            $historyGroups = Order::groupsForPage($historyPage, ['customers']);
        }

        return view('driver.home', compact('activeGroups', 'historyGroups', 'historyPage'));
    }

    public function deliver($groupId) {
        $driver = $this->getDriver();
        abort_if(!$driver, 404);
        $affected = Order::where('order_group_id', $groupId)
                         ->where('driver_id', $driver->id)
                         ->update(['status' => 'delivered', 'delivered_at' => now()]);
        abort_if($affected === 0, 404);
        return back()->with('success', 'Commande marquée comme livrée.');
    }
}
