<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    /**
     * Changer d'adresse e-mail permet ensuite de réinitialiser le mot de passe : le mot de passe actuel
     * est donc exigé, pour qu'une session volée ne suffise pas à s'approprier le compte.
     */
    public function update(Request $request): RedirectResponse
    {
        $emailChanged = $request->input('email') !== $request->user()->email;

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'email_password' => $emailChanged ? ['required', 'current_password'] : ['nullable'],
        ], [
            'email_password.required' => "Saisissez votre mot de passe pour changer d'adresse e-mail.",
        ]);

        $request->user()->update(['name' => $data['name'], 'email' => $data['email']]);

        return back()->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Mot de passe modifié.');
    }

    /** Les billets restent pour l'historique ; ceux des épreuves à venir sont annulés (voir User::booted). */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['delete_password' => 'required|current_password']);
        $user = $request->user();

        if ($user->isAdmin() && User::where('role', Role::Admin)->count() === 1) {
            return back()->withErrors(['delete_password' => 'Vous êtes le seul administrateur : nommez-en un autre avant de supprimer votre compte.']);
        }

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Votre compte a été supprimé.');
    }
}
