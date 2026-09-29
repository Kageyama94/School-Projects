<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

#[Fillable(['identifiant', 'name', 'password', 'role', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'must_change_password' => 'boolean',
        ];
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    /**
     * Premier identifiant attribué aux comptes réels (les comptes de démo utilisent 1, 2, 3).
     */
    public const FIRST_REAL_IDENTIFIANT = 94020000;

    public const LAST_IDENTIFIANT = 99999999;

    /**
     * Génère l'identifiant de connexion (numérique de 8 chiffres au plus) : le premier numéro libre à partir de 94020000.
     * Enseignants et étudiants partagent la même suite ; un identifiant modifié à la main ne peut donc pas la bloquer.
     */
    public static function generateIdentifiant(int $first = self::FIRST_REAL_IDENTIFIANT, int $last = self::LAST_IDENTIFIANT): string
    {
        $taken = static::query()
            ->whereRaw('CAST(identifiant AS INTEGER) >= ?', [$first])
            ->orderByRaw('CAST(identifiant AS INTEGER)')
            ->pluck('identifiant')
            ->map(fn ($identifiant) => (int) $identifiant);

        $candidate = $first;

        foreach ($taken as $number) {
            if ($number > $candidate) {
                break;
            }

            if ($number === $candidate) {
                $candidate++;
            }
        }

        if ($candidate > $last) {
            throw new RuntimeException('Plus aucun identifiant disponible (limite de 8 chiffres atteinte).');
        }

        return (string) $candidate;
    }

    /**
     * Mot de passe par défaut généré à partir du nom et prénom (format nom_prenom).
     */
    public static function defaultPassword(string $firstName, string $lastName): string
    {
        return Str::slug($lastName, '_').'_'.Str::slug($firstName, '_');
    }

    /**
     * Mot de passe initial de cette personne, ou null si le compte n'a pas de fiche (ex. admin).
     */
    public function initialPassword(): ?string
    {
        $profile = $this->teacher ?? $this->student;

        return $profile ? static::defaultPassword($profile->first_name, $profile->last_name) : null;
    }

    /**
     * Déconnecte cette personne partout, sauf éventuellement la session en cours : supprime ses sessions
     * (pilote « database ») et renouvelle le jeton « Se souvenir de moi », sans quoi un appareil mémorisé se reconnecterait seul.
     */
    public function endSessions(?string $exceptSessionId = null): void
    {
        $this->forceFill(['remember_token' => Str::random(60)])->save();

        DB::table(config('session.table'))
            ->where('user_id', $this->id)
            ->when($exceptSessionId, fn ($query) => $query->where('id', '!=', $exceptSessionId))
            ->delete();
    }
}
