<?php

declare(strict_types=1);

namespace App\Support;

final class Positions
{
    private const ALL = [
        'POR' => 'Portiere',
        'TD'  => 'Terzino destro',
        'TS'  => 'Terzino sinistro',
        'DC'  => 'Difensore centrale',
        'CC'  => 'Centrocampista centrale',
        'MED' => 'Mediano',
        'CS'  => 'Centrocampista sinistro',
        'CD'  => 'Centrocampista destro',
        'TRQ' => 'Trequartista',
        'AS'  => 'Ala sinistra',
        'AD'  => 'Ala destra',
        'ATT' => 'Attaccante',
    ];

    public static function all(): array
    {
        return self::ALL;
    }

    public static function codes(): array
    {
        return array_keys(self::ALL);
    }

    public static function label(string $code): string
    {
        return self::ALL[$code] ?? $code;
    }

    public static function exists(string $code): bool
    {
        return array_key_exists($code, self::ALL);
    }
}