<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SignOutOtherDevices;
use App\Http\Controllers\Controller;
use App\Rules\NewPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function show(): View
    {
        return view('auth.force-password-change');
    }

    public function update(Request $request, SignOutOtherDevices $signOutOtherDevices): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults(), new NewPassword($request->user())],
        ]);

        $request->user()->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
        ])->save();
        $signOutOtherDevices->handle($request);

        return redirect()->route('dashboard');
    }
}
