<?php

namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignOutOtherDevices
{
    /**
     * Après un changement de mot de passe : déconnecte les autres appareils, en gardant la session en cours.
     * Le jeton « Se souvenir de moi » étant renouvelé, l'appareil en cours reçoit un cookie à jour s'il en avait un.
     */
    public function handle(Request $request): void
    {
        $user = $request->user();
        $user->endSessions($request->session()->getId());

        if ($request->hasCookie(Auth::guard()->getRecallerName())) {
            Auth::guard()->login($user, remember: true);
        }
    }
}
