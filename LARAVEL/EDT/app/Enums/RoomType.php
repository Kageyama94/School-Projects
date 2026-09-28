<?php

namespace App\Enums;

enum RoomType: string
{
    case Salle = 'salle';
    case Gymnase = 'gymnase';
    case Informatique = 'informatique';

    public function label(): string
    {
        return match ($this) {
            self::Salle => 'Salle',
            self::Gymnase => 'Gymnase',
            self::Informatique => 'Salle informatique',
        };
    }
}
