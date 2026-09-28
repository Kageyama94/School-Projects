<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AsksForAPassword;
use App\Console\Commands\Concerns\ValidatesInput;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class ResetAdminPassword extends Command
{
    use AsksForAPassword;
    use ValidatesInput;

    protected $signature = 'app:reset-admin-password
        {identifiant : Identifiant de l\'administrateur}
        {--password= : Nouveau mot de passe (à éviter : il reste dans l\'historique du terminal)}';

    protected $description = 'Définit un nouveau mot de passe pour un administrateur (mot de passe oublié).';

    public function handle(): int
    {
        $admin = User::where('identifiant', $this->argument('identifiant'))->first();

        if (! $admin?->isAdmin()) {
            $this->error('Aucun administrateur avec cet identifiant.');

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->askPassword('Nouveau mot de passe');

        $validated = $this->validateOrFail(
            ['password' => $password],
            ['password' => ['required', Password::defaults()]],
            ['password' => 'mot de passe'],
        );

        if ($validated === null) {
            return self::FAILURE;
        }

        $admin->forceFill(['password' => $validated['password'], 'must_change_password' => false])->save();
        DB::table(config('session.table'))->where('user_id', $admin->id)->delete();

        $this->info("Mot de passe de {$admin->name} mis à jour. Ses sessions ouvertes ont été fermées.");

        return self::SUCCESS;
    }
}
