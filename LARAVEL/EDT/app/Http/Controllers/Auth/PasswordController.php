<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SignOutOtherDevices;
use App\Http\Controllers\Controller;
use App\Rules\NewPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function update(Request $request, SignOutOtherDevices $signOutOtherDevices): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'password' => ['required', 'confirmed', Password::defaults(), new NewPassword($request->user())],
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);
        // Les autres appareils (session ouverte ou « Se souvenir de moi ») doivent se reconnecter.
        $signOutOtherDevices->handle($request);

        return back()->with('success', 'Mot de passe mis à jour.');
    }
}
