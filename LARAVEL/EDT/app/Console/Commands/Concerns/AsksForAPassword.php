<?php

namespace App\Console\Commands\Concerns;

trait AsksForAPassword
{
    /**
     * Demande un mot de passe et sa confirmation ; renvoie null si les deux saisies ne correspondent pas.
     */
    private function askPassword(string $label = 'Mot de passe'): ?string
    {
        $password = $this->secret("{$label} (8 caractères minimum, lettres et chiffres)");

        if ($password !== $this->secret('Confirmer le mot de passe')) {
            $this->error('Les deux mots de passe ne correspondent pas.');

            return null;
        }

        return $password;
    }
}
