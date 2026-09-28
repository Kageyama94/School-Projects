<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

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

        // Ferme les sessions déjà ouvertes de cette personne (pilote de session « database »).
        DB::table(config('session.table'))->where('user_id', $user->id)->delete();

        return redirect()->back()->with('credentials', ['message' => 'Mot de passe réinitialisé.', 'password' => $password]);
    }
}
