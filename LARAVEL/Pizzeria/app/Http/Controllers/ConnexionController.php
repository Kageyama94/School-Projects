<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConnexionController extends Controller
{
    private function redirectByRole() {
        return match(Auth::user()->role) {
            'admin' => redirect()->route('admin'),
            'driver' => redirect()->route('driver.home'),
            default => redirect()->route('customer.home', Auth::id()),
        };
    }

    public function register() {
        if (Auth::check()) return $this->redirectByRole();
        return view('connexion/register');
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required|unique:users',
            'password' => 'required|min:4|confirmed',
        ]);

        User::create([
            'name' => $request->get('name'),
            'password' => $request->get('password'),
            'role' => 'customer',
        ]);

        return redirect()->route('login')->with('success', 'Compte créé, connectez-vous.');
    }

    public function login() {
        if (Auth::check()) return $this->redirectByRole();
        return view('connexion/login');
    }

    public function authenticate(Request $request) {
        $credentials = $request->validate([
            'name' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return $this->redirectByRole();
        }

        return back()->withErrors(['name' => 'Identifiants incorrects.']);
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
