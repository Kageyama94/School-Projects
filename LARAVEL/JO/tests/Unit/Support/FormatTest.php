<?php

namespace Tests\Unit\Support;

use App\Support\Format;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
{
    private const THIN_SPACE = "\u{202F}";

    private const NO_BREAK_SPACE = "\u{00A0}";

    public function test_numbers_use_a_non_breaking_thousands_separator(): void
    {
        $this->assertSame('77'.self::THIN_SPACE.'000', Format::number(77000));
        $this->assertSame('1'.self::THIN_SPACE.'250'.self::NO_BREAK_SPACE.'€', Format::euros(1250));
    }

    /**
     * @return array<string, array{float, string}>
     */
    public static function rates(): array
    {
        return [
            'aucune vente' => [0.0, '0'.self::NO_BREAK_SPACE.'%'],
            'moins de 1 %' => [0.004, '<'.self::NO_BREAK_SPACE.'1'.self::NO_BREAK_SPACE.'%'],
            'arrondi' => [0.426, '43'.self::NO_BREAK_SPACE.'%'],
            'plus de 99 % sans être complet' => [0.996, '>'.self::NO_BREAK_SPACE.'99'.self::NO_BREAK_SPACE.'%'],
            'complet' => [1.0, '100'.self::NO_BREAK_SPACE.'%'],
        ];
    }

    #[DataProvider('rates')]
    public function test_percent_never_shows_empty_or_full_when_it_is_not(float $rate, string $expected): void
    {
        $this->assertSame($expected, Format::percent($rate));
    }
}
