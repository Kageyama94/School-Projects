<?php

namespace App\Enums;

enum Level: string
{
    case L1 = 'l1';
    case L2 = 'l2';
    case L3 = 'l3';
    case M1 = 'm1';
    case M2 = 'm2';

    public function label(): string
    {
        return match ($this) {
            self::L1 => 'Licence 1',
            self::L2 => 'Licence 2',
            self::L3 => 'Licence 3',
            self::M1 => 'Master 1',
            self::M2 => 'Master 2',
        };
    }

    public function short(): string
    {
        return strtoupper($this->value);
    }
}
