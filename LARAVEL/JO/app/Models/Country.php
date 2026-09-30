<?php

namespace App\Models;

use App\Enums\Medal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

#[Fillable(['name', 'code', 'iso'])]
class Country extends Model
{
    public $timestamps = false;

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /** /pays/fra et /pays/FRA mènent au même pays. */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->where($field ?? 'code', Str::upper((string) $value))->first();
    }

    /**
     * Drapeau en image (public/img/flags, copié depuis flagcdn.com) : les emojis drapeaux ne
     * s'affichent pas sous Windows, et le site marche sans internet. Décoratif par défaut (texte
     * alternatif vide), car le nom du pays est presque toujours écrit à côté.
     */
    public function flag(string $class = 'flag', bool $decorative = true): HtmlString
    {
        return new HtmlString(sprintf(
            '<img src="%s" alt="%s" class="%s" loading="lazy">',
            e(asset("img/flags/{$this->iso}.png")), $decorative ? '' : e($this->name), e($class),
        ));
    }

    /**
     * Pays du monde disponibles, un drapeau existe pour chacun.
     *
     * @return array<string, string> code ISO => nom français
     */
    public static function worldList(): array
    {
        return json_decode(file_get_contents(resource_path('data/pays.json')), true);
    }

    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    /** Pays pouvant être médaillés dans une épreuve par équipes : au moins un athlète éligible. */
    public function scopeEligibleFor(Builder $query, Event $event): void
    {
        $query->whereHas('athletes', fn (Builder $query) => $query->eligibleFor($event));
    }

    /** Pays médaillés avec leur décompte, triés comme un tableau olympique (or, puis argent, puis bronze). */
    public static function medalTable(): Builder
    {
        $query = static::query()
            ->join('results', 'results.country_id', '=', 'countries.id')
            ->groupBy('countries.id', 'countries.name', 'countries.code', 'countries.iso')
            ->select('countries.*');

        foreach (Medal::cases() as $medal) {
            $query->selectRaw("SUM(CASE WHEN results.medal = ? THEN 1 ELSE 0 END) as {$medal->value}", [$medal->value]);
        }

        return $query->selectRaw('COUNT(results.id) as total')
            ->orderByDesc(Medal::Gold->value)
            ->orderByDesc(Medal::Silver->value)
            ->orderByDesc(Medal::Bronze->value)
            ->orderBy('countries.name');
    }
}
