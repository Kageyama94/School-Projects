<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    /**
     * Suppression du compte : ses billets restent pour l'historique (user_id devient NULL en base),
     * ceux des épreuves à venir sont annulés pour libérer les places.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->tickets()->active()
                ->whereHas('event', fn (Builder $query) => $query->where('starts_at', '>', now()))
                ->update(['cancelled_at' => now()]);
        });
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }
}
