<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Recherche par nom pour les fiches qui ont un prénom et un nom (enseignants, étudiants).
 */
trait MatchesName
{
    /**
     * Filtre les fiches dont le prénom ou le nom contient chaque mot recherché.
     *
     * @param  Builder<static>  $query
     */
    public function scopeMatchingName(Builder $query, string $search): void
    {
        foreach (preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $query->where(fn (Builder $q) => $q
                ->where('first_name', 'like', "%{$word}%")
                ->orWhere('last_name', 'like', "%{$word}%"));
        }
    }
}
