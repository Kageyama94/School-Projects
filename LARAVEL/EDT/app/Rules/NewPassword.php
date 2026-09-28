<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class NewPassword implements ValidationRule
{
    public function __construct(private readonly User $user) {}

    /**
     * Refuse le mot de passe actuel et le mot de passe initial (nom_prénom), faciles à deviner.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (Hash::check($value, $this->user->password)) {
            $fail('Le nouveau mot de passe doit être différent du mot de passe actuel.');
        } elseif ($value === $this->user->initialPassword()) {
            $fail('Le nouveau mot de passe ne peut pas être le mot de passe initial (nom_prénom).');
        }
    }
}
