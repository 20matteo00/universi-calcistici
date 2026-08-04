<?php

declare(strict_types=1);

namespace App\Support;

final class CompetitionTypes
{
    private const ALL = [
        'lega' => 'Lega',
        'eliminazione_diretta' => 'Eliminazione diretta',
        'gironi' => 'Gironi',
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
