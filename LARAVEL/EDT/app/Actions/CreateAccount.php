<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CreateAccount
{
    /**
     * Crée le compte de connexion et sa fiche (enseignant ou étudiant) de façon atomique.
     * Réessaie si deux créations simultanées ont calculé le même identifiant.
     *
     * @param  array{first_name: string, last_name: string}  $names
     * @param  Closure(User): mixed  $createProfile
     * @return array{identifiant: string, password: string}
     */
    public function handle(UserRole $role, array $names, Closure $createProfile): array
    {
        $password = User::defaultPassword($names['first_name'], $names['last_name']);

        $user = retry(3, fn () => DB::transaction(function () use ($role, $names, $password, $createProfile) {
            $user = User::create([
                'identifiant' => User::generateIdentifiant(),
                'name' => "{$names['first_name']} {$names['last_name']}",
                'password' => $password,
                'role' => $role,
                'must_change_password' => true,
            ]);

            $createProfile($user);

            return $user;
        }), 0, fn ($e) => $e instanceof UniqueConstraintViolationException);

        return ['identifiant' => $user->identifiant, 'password' => $password];
    }
}
