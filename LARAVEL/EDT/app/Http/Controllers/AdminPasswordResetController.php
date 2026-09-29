<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AdminPasswordResetController extends Controller
{
    /**
     * Remet le mot de passe initial (nom_prénom) d'un enseignant ou d'un étudiant.
     */
    public function __invoke(User $user): RedirectResponse
    {
        $password = $user->initialPassword();

        abort_if($password === null, 404);

        $user->forceFill([
            'password' => $password,
            'must_change_password' => true,
        ])->save();

        $user->endSessions();

        return redirect()->back()->with('credentials', ['message' => 'Mot de passe réinitialisé.', 'password' => $password]);
    }
}
