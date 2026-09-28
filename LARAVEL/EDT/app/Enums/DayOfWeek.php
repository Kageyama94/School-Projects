<?php

namespace App\Enums;

enum DayOfWeek: int
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    /**
     * Jours où des cours peuvent avoir lieu (lundi à samedi).
     *
     * @return array<int, self>
     */
    public static function schoolDays(): array
    {
        return array_filter(self::cases(), fn (self $day) => $day !== self::Sunday);
    }

    /**
     * Code du jour dans une règle de récurrence iCalendar (BYDAY).
     */
    public function icalCode(): string
    {
        return match ($this) {
            self::Monday => 'MO',
            self::Tuesday => 'TU',
            self::Wednesday => 'WE',
            self::Thursday => 'TH',
            self::Friday => 'FR',
            self::Saturday => 'SA',
            self::Sunday => 'SU',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Monday => 'Lundi',
            self::Tuesday => 'Mardi',
            self::Wednesday => 'Mercredi',
            self::Thursday => 'Jeudi',
            self::Friday => 'Vendredi',
            self::Saturday => 'Samedi',
            self::Sunday => 'Dimanche',
        };
    }
}
