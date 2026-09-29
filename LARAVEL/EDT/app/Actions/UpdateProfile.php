<?php

namespace App\Actions;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Arr;

class UpdateProfile
{
    /**
     * Règles communes à la création et à la modification d'une fiche : prénom et nom. L'identifiant de connexion n'est pas modifiable.
     *
     * @return array<string, array<mixed>>
     */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Enregistre la fiche et garde le nom d'affichage du compte synchronisé.
     */
    public function handle(Teacher|Student $person, array $validated): void
    {
        $person->update(Arr::only($validated, ['first_name', 'last_name', 'group_id']));

        $person->user?->update(['name' => "{$validated['first_name']} {$validated['last_name']}"]);
    }
}
