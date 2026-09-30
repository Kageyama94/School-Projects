<?php

namespace App\Enums;

/** Catégorie d'une épreuve (F, H ou mixte) et genre d'un athlète (F ou H). */
enum Gender: string
{
    case Women = 'F';
    case Men = 'H';
    case Mixed = 'M';

    /** Libellé d'une catégorie d'épreuve : « Femmes », « Hommes », « Mixte ». */
    public function label(): string
    {
        return match ($this) {
            self::Women => 'Femmes',
            self::Men => 'Hommes',
            self::Mixed => 'Mixte',
        };
    }

    /** Libellé pour un athlète : « Femme » ou « Homme ». */
    public function singularLabel(): string
    {
        return match ($this) {
            self::Women => 'Femme',
            self::Men => 'Homme',
            self::Mixed => 'Mixte',
        };
    }

    /**
     * Genres possibles pour un athlète (une épreuve peut en plus être mixte).
     *
     * @return list<self>
     */
    public static function forAthletes(): array
    {
        return [self::Women, self::Men];
    }
}
