<?php

namespace Tests;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * AuthenticateSession garde en session l'empreinte du mot de passe de l'utilisateur connecté.
     * Quand un test passe d'un utilisateur à un autre, on repart d'une session vierge, comme après
     * une vraie déconnexion ; sinon le second utilisateur serait déconnecté.
     */
    public function actingAs(Authenticatable $user, $guard = null): static
    {
        $current = $this->app['auth']->guard($guard)->user();
        if ($current && $current->getAuthIdentifier() !== $user->getAuthIdentifier()) {
            $this->flushSession();
        }

        return parent::actingAs($user, $guard);
    }

    /** Compte administrateur créé par le seeder (admin@jo.test). */
    protected function admin(): User
    {
        return User::where('role', Role::Admin)->firstOrFail();
    }

    /** Compte de démo créé par le seeder (spectateur@jo.test), avec des billets. */
    protected function spectator(): User
    {
        return User::where('email', 'spectateur@jo.test')->firstOrFail();
    }

    protected function upcomingEventWithoutTickets(): Event
    {
        return Event::upcoming()->whereDoesntHave('tickets')->firstOrFail();
    }

    /** Épreuve individuelle terminée et non annulée, éventuellement d'un sport ou d'une catégorie donnés. */
    protected function pastIndividualEvent(?string $sport = null, ?Gender $gender = null): Event
    {
        return Event::where('starts_at', '<', now())->whereNull('cancelled_at')->where('team', false)
            ->when($sport, fn (Builder $query) => $query->whereHas('sport', fn (Builder $query) => $query->where('name', $sport)))
            ->when($gender, fn (Builder $query) => $query->where('gender', $gender))
            ->firstOrFail();
    }
}
