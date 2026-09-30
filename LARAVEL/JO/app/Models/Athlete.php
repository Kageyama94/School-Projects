<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['first_name', 'last_name', 'gender', 'country_id', 'sport_id'])]
class Athlete extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['gender' => Gender::class];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    /** Athlètes pouvant être médaillés dans l'épreuve : même sport, et même genre sauf épreuve mixte. */
    public function scopeEligibleFor(Builder $query, Event $event): void
    {
        $query->where('sport_id', $event->sport_id)
            ->when($event->gender !== Gender::Mixed, fn (Builder $query) => $query->where('gender', $event->gender));
    }

    public function fullName(): string
    {
        return $this->first_name.' '.mb_strtoupper($this->last_name);
    }
}
