<?php

namespace App\Models;

use Database\Factories\LicenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Licence extends Model
{
    /** @use HasFactory<LicenceFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Group::class);
    }

    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(Student::class, Group::class);
    }
}
