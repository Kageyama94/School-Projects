<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\Medal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sport_id', 'venue_id', 'name', 'gender', 'team', 'starts_at', 'price', 'capacity'])]
class Event extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'starts_at' => 'datetime',
            'team' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** Épreuves qui auront bien lieu : pas encore commencées et non annulées. */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>', now())->whereNull('cancelled_at')->orderBy('starts_at');
    }

    public function isPast(): bool
    {
        return $this->starts_at->isPast();
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function genderLabel(): string
    {
        return $this->gender->label();
    }

    /** « Judo — -73 kg ». Le sport doit être chargé (with('sport')). */
    public function title(): string
    {
        return "{$this->sport->name} — {$this->name}";
    }

    /** « 🥋 Judo — -73 kg (Hommes) » : sans ambiguïté entre les épreuves féminine et masculine. */
    public function fullTitle(): string
    {
        return "{$this->sport->icon} {$this->title()} ({$this->genderLabel()})";
    }

    /**
     * Places du podium à attribuer, par champ du formulaire de résultats : deux bronzes dans les sports de combat.
     * Le sport doit être chargé.
     *
     * @return array<string, Medal>
     */
    public function medalSlots(): array
    {
        $slots = ['gold' => Medal::Gold, 'silver' => Medal::Silver, 'bronze' => Medal::Bronze];
        if ($this->sport->two_bronzes) {
            $slots['bronze_2'] = Medal::Bronze;
        }

        return $slots;
    }

    /** Pourquoi les résultats ne peuvent pas être saisis (épreuve annulée ou pas encore commencée), sinon null. */
    public function resultsLockedReason(): ?string
    {
        return match (true) {
            $this->isCancelled() => 'Cette épreuve a été annulée : pas de résultats à saisir.',
            ! $this->isPast() => "Les résultats se saisissent une fois l'épreuve commencée.",
            default => null,
        };
    }

    public function seatsLeft(): int
    {
        return max(0, $this->capacity - $this->seatsSold());
    }

    /** Places occupées par des billets non annulés. */
    public function seatsSold(): int
    {
        return (int) $this->tickets()->active()->sum('quantity');
    }
}
