<?php

namespace App\Models;

use App\Enums\Medal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'country_id', 'athlete_id', 'medal'])]
class Result extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['medal' => Medal::class];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** Nom du médaillé : l'athlète, ou le pays pour une épreuve par équipes. */
    public function winnerName(): string
    {
        return $this->athlete?->fullName() ?? $this->country->name;
    }
}
