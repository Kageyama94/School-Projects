<?php

namespace App\Models;

use App\Enums\Medal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'icon', 'description', 'two_bronzes'])]
class Sport extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['two_bronzes' => 'boolean'];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    /** Une épreuve du sport a-t-elle déjà deux médailles de bronze ? */
    public function hasEventWithTwoBronzes(): bool
    {
        return Result::where('medal', Medal::Bronze)
            ->whereIn('event_id', $this->events()->select('id'))
            ->groupBy('event_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }
}
