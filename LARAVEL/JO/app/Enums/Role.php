<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Spectator = 'spectator';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Spectator => 'Spectateur',
        };
    }
}
