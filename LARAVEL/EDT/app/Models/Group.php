<?php

namespace App\Models;

use App\Enums\Level;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected $fillable = ['licence_id', 'level', 'name'];

    protected function casts(): array
    {
        return [
            'level' => Level::class,
        ];
    }

    public function licence(): BelongsTo
    {
        return $this->belongsTo(Licence::class);
    }

    /**
     * Classe par licence, puis niveau, puis nom.
     *
     * @param  Builder<Group>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy(Licence::select('name')->whereColumn('licences.id', 'groups.licence_id'))
            ->orderBy('level')
            ->orderBy('name');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Nom complet sans ambiguïté : « Informatique · L2 · Groupe A ».
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn () => collect([$this->licence?->name, $this->level?->short(), $this->name])->filter()->implode(' · '));
    }
}
