<?php

namespace App\Http\Controllers;

use App\Models\Pizza;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function home($userId) {
        $customer = Customer::where('user_id', Auth::id())->first();
        $orders = $customer
            ? $customer->orders()->orderBy('created_at', 'desc')->get()
            : collect();
        return view('customer/home', compact('orders'));
    }

    public function form($userId) {
        $customer = Customer::where('user_id', Auth::id())->first();
        return view('customer/order', [
            'pizzas' => Pizza::all(),
            'customer' => $customer,
        ]);
    }

    public function reservation(Request $request, $userId) {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'address' => 'required',
            'postal_code' => 'required',
            'pizza' => 'nullable|array',
            'pizza.*' => 'integer|min:0|max:99',
        ]);

        $pizzaInput = array_filter($request->input('pizza', []));

        if (empty($pizzaInput)) {
            return back()->withErrors(['pizza' => 'Veuillez sélectionner au moins une pizza.'])->withInput();
        }

        $pizzas = Pizza::whereIn('id', array_keys($pizzaInput))->get()->keyBy('id');

        $customer = Customer::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
                'email' => $request->get('email'),
                'phone' => $request->get('phone'),
            ]
        );

        $summary = [];
        $grandTotal = 0;

        foreach ($pizzaInput as $pizzaId => $quantity) {
            if (!isset($pizzas[$pizzaId])) continue;

            $pizza = $pizzas[$pizzaId];
            $subtotal = $quantity * $pizza->price;

            $order = new Order();
            $order->pizza_name = $pizza->name;
            $order->unit_price = $pizza->price;
            $order->quantity = $quantity;
            $order->address = $request->get('address');
            $order->postal_code = $request->get('postal_code');
            $order->save();
            $customer->orders()->attach($order);

            $summary[] = ['name' => $pizza->name, 'unit_price' => $pizza->price, 'quantity' => $quantity, 'subtotal' => $subtotal];
            $grandTotal += $subtotal;
        }

        return redirect()->route('basket.confirm', Auth::id())
            ->with('summary', $summary)
            ->with('grandTotal', $grandTotal)
            ->with('orderAddress', $request->get('address'));
    }

    public function basket() {
        if (!session('summary')) {
            return redirect()->route('customer.home', Auth::id());
        }
        return view('customer/basket');
    }
}
