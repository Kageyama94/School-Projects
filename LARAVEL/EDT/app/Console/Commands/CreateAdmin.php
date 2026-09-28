<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AsksForAPassword;
use App\Console\Commands\Concerns\ValidatesInput;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    use AsksForAPassword;
    use ValidatesInput;

    protected $signature = 'app:create-admin
        {--name= : Nom affiché}
        {--identifiant= : Identifiant de connexion (8 chiffres au plus)}
        {--password= : Mot de passe (à éviter : il reste dans l\'historique du terminal)}';

    protected $description = 'Crée un compte administrateur (à utiliser en production à la place du seeder).';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nom de l\'administrateur', 'Administrateur');
        $identifiant = $this->option('identifiant') ?: $this->ask('Identifiant de connexion', $this->suggestedIdentifiant());
        $password = $this->option('password') ?: $this->askPassword();

        $validated = $this->validateOrFail(
            ['name' => $name, 'identifiant' => $identifiant, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'identifiant' => ['required', 'regex:/^\d+$/', 'max:8', 'unique:users,identifiant'],
                'password' => ['required', Password::defaults()],
            ],
            ['name' => 'nom', 'identifiant' => 'identifiant', 'password' => 'mot de passe'],
        );

        if ($validated === null) {
            return self::FAILURE;
        }

        User::create([
            'identifiant' => $validated['identifiant'],
            'name' => $validated['name'],
            'password' => $validated['password'],
            'role' => UserRole::Admin,
            'must_change_password' => false,
        ]);

        $this->info("Administrateur créé. Identifiant : {$validated['identifiant']}");

        return self::SUCCESS;
    }

    /**
     * Un petit numéro (1, 2…) plutôt que la suite des comptes réels, qui commence à 94020000.
     */
    private function suggestedIdentifiant(): string
    {
        $candidate = 1;

        while (User::where('identifiant', (string) $candidate)->exists()) {
            $candidate++;
        }

        return (string) $candidate;
    }
}
