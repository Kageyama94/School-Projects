<?php

namespace App\Http\Controllers;

use App\Models\Pizza;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    private function getCustomer(): ?Customer {
        return Customer::where('user_id', Auth::id())->first();
    }

    public function home($userId) {
        $customer = $this->getCustomer();

        $groups     = collect();
        $groupsPage = null;

        if ($customer) {
            $groupsPage = Order::join('customer_order', 'customer_order.order_id', '=', 'orders.id')
                ->where('customer_order.customer_id', $customer->id)
                ->whereNotNull('orders.order_group_id')
                ->selectRaw('orders.order_group_id, MAX(orders.created_at) as latest_at')
                ->groupBy('orders.order_group_id')
                ->orderByDesc('latest_at')
                ->paginate(10);

            $customerOrders = Order::whereIn('order_group_id', $groupsPage->pluck('order_group_id'))->get();
            $groups = $groupsPage->pluck('order_group_id')
                ->mapWithKeys(fn($groupId) => [$groupId => $customerOrders->where('order_group_id', $groupId)]);
        }

        return view('customer/home', compact('groups', 'groupsPage', 'customer'));
    }

    public function form($userId) {
        $customer = $this->getCustomer();
        return view('customer/order', [
            'pizzas' => Pizza::orderBy('name')->get(),
            'customer' => $customer,
        ]);
    }

    public function reservation(Request $request, $userId) {
        $existingCustomerId = $this->getCustomer()?->id;
        $validated = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => ['required', 'email', Rule::unique('customers', 'email')->ignore($existingCustomerId)],
            'phone' => 'required',
            'address' => 'required',
            'postal_code'=> 'required',
            'pizza' => 'nullable|array',
            'pizza.*' => 'integer|min:0|max:99',
        ]);

        $pizzaInput = array_filter($validated['pizza'] ?? []);

        if (empty($pizzaInput)) {
            return back()->withErrors(['pizza' => 'Veuillez sélectionner au moins une pizza.'])->withInput();
        }

        $pizzas = Pizza::whereIn('id', array_keys($pizzaInput))->get()->keyBy('id');

        $summary = [];
        $grandTotal = 0;
        $groupId = Str::uuid()->toString();

        DB::transaction(function () use ($pizzaInput, $pizzas, $validated, $groupId, &$summary, &$grandTotal) {
            $customer = Customer::updateOrCreate(
                ['user_id' => Auth::id()],
                array_intersect_key($validated, array_flip(['first_name', 'last_name', 'email', 'phone']))
            );

            $orderIds = [];
            foreach ($pizzaInput as $pizzaId => $quantity) {
                if (!isset($pizzas[$pizzaId])) continue;

                $pizza = $pizzas[$pizzaId];
                $subtotal = $quantity * $pizza->price;

                $order = Order::create([
                    'order_group_id' => $groupId,
                    'pizza_name' => $pizza->name,
                    'unit_price' => $pizza->price,
                    'quantity' => $quantity,
                    'address' => $validated['address'],
                    'postal_code' => $validated['postal_code'],
                    'status' => 'pending',
                ]);

                $orderIds[] = $order->id;
                $summary[] = ['name' => $pizza->name, 'unit_price' => $pizza->price, 'quantity' => $quantity, 'subtotal' => $subtotal];
                $grandTotal += $subtotal;
            }

            $customer->orders()->attach($orderIds);
        });

        return redirect()->route('order.confirm', Auth::id())
            ->with('summary', $summary)
            ->with('grandTotal', $grandTotal)
            ->with('orderAddress', $validated['address'])
            ->with('customerFirstName', $validated['first_name']);
    }

    public function cancelOrder($userId, $groupId) {
        $customer = $this->getCustomer();
        abort_if(!$customer, 404);
        $order = $customer->orders()->where('order_group_id', $groupId)->first();
        abort_if(!$order, 404);
        if ($order->status !== 'pending') {
            return back()->withErrors(['cancel' => 'Cette commande ne peut plus être annulée.']);
        }
        Order::where('order_group_id', $groupId)->delete();
        return redirect()->route('customer.home', Auth::id())->with('success', 'Commande annulée.');
    }

    public function orderDetail($userId, $groupId) {
        $customer = $this->getCustomer();
        abort_if(!$customer, 404);
        $orders = $customer->orders()->where('order_group_id', $groupId)->get();
        abort_if($orders->isEmpty(), 404);
        return view('customer/orderDetail', compact('orders'));
    }

    public function editProfile($userId) {
        $customer = $this->getCustomer();
        return view('customer/profile', compact('customer'));
    }

    public function updateProfile(Request $request, $userId) {
        $existingCustomerId = $this->getCustomer()?->id;
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => ['required', 'email', Rule::unique('customers', 'email')->ignore($existingCustomerId)],
            'phone' => 'required',
        ]);

        Customer::updateOrCreate(['user_id' => Auth::id()], $request->only(['first_name', 'last_name', 'email', 'phone']));

        return redirect()->route('customer.home', Auth::id())->with('success', 'Profil mis à jour.');
    }

    public function basket() {
        if (!session('summary')) {
            return redirect()->route('customer.home', Auth::id());
        }
        return view('customer/basket');
    }
}
