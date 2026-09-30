<?php

namespace App\Support;

/**
 * Formats d'affichage à la française. Number::format() de Laravel demanderait l'extension PHP intl,
 * absente ici. Utilisé dans les vues via les directives @number, @euros et @percent.
 */
class Format
{
    /** Espace fine insécable : séparateur des milliers en français, jamais coupé en fin de ligne. */
    private const THOUSANDS = "\u{202F}";

    public static function number(int|float $value): string
    {
        return number_format($value, 0, ',', self::THOUSANDS);
    }

    public static function euros(int|float $value): string
    {
        return self::number($value)."\u{00A0}€";
    }

    /**
     * L'arrondi ne doit jamais faire croire à une épreuve vide ou complète : un taux non nul sous 1 %
     * s'affiche « < 1 % » (et non « 0 % »), un taux au-dessus de 99 % mais incomplet « > 99 % » (et non « 100 % »).
     */
    public static function percent(float $rate): string
    {
        return match (true) {
            $rate > 0 && $rate < 0.01 => "<\u{00A0}1\u{00A0}%",
            $rate > 0.99 && $rate < 1 => ">\u{00A0}99\u{00A0}%",
            default => round($rate * 100)."\u{00A0}%",
        };
    }
}
