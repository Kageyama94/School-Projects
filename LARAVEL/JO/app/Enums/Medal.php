<?php

namespace App\Enums;

enum Medal: string
{
    case Gold = 'gold';
    case Silver = 'silver';
    case Bronze = 'bronze';

    /** Nom court affiché dans les compteurs : « Or », « Argent », « Bronze ». */
    public function label(): string
    {
        return match ($this) {
            self::Gold => 'Or',
            self::Silver => 'Argent',
            self::Bronze => 'Bronze',
        };
    }

    public function fullLabel(): string
    {
        return match ($this) {
            self::Gold => "Médaille d'or",
            self::Silver => "Médaille d'argent",
            self::Bronze => 'Médaille de bronze',
        };
    }

    /** Marche du podium : 1, 2 ou 3. */
    public function rank(): int
    {
        return match ($this) {
            self::Gold => 1,
            self::Silver => 2,
            self::Bronze => 3,
        };
    }

    /**
     * Ordre d'affichage du podium, l'or au centre.
     *
     * @return list<self>
     */
    public static function podiumOrder(): array
    {
        return [self::Silver, self::Gold, self::Bronze];
    }
}
