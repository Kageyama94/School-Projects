<?php

namespace App\Models;

use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'first_name', 'last_name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => "{$this->first_name} {$this->last_name}");
    }

    /**
     * Filtre les enseignants dont le prénom ou le nom contient chaque mot recherché.
     *
     * @param  Builder<Teacher>  $query
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
