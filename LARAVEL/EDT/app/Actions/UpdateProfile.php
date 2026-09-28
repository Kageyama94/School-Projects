<?php

namespace App\Actions;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateProfile
{
    /**
     * Règles communes à la modification d'une fiche : prénom, nom et identifiant de connexion (si un compte existe).
     *
     * @return array<string, array<mixed>>
     */
    public static function rules(Teacher|Student $person): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ];

        if ($person->user_id) {
            $rules['identifiant'] = ['required', 'regex:/^\d+$/', 'max:8', Rule::unique('users')->ignore($person->user_id)];
        }

        return $rules;
    }

    /**
     * Enregistre la fiche et garde le nom d'affichage et l'identifiant du compte synchronisés.
     */
    public function handle(Teacher|Student $person, array $validated): void
    {
        $person->update(Arr::only($validated, ['first_name', 'last_name', 'group_id']));

        $person->user?->update([
            'name' => "{$validated['first_name']} {$validated['last_name']}",
            'identifiant' => $validated['identifiant'],
        ]);
    }
}
