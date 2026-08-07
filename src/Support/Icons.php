<?php

declare(strict_types=1);

namespace App\Support;

final class Icons
{
    private const EVENT_ICONS = [
        'gol' => 'game-icons:soccer-ball',
        'gol_rigore' => 'emojione:goal-net',
        'rigore_sbagliato' => 'icon-park-solid:error',
        'ammonizione' => 'openmoji:yellow-square',
        'espulsione' => 'openmoji:red-square',
        'assist' => 'streamline-ultimate-color:soccer-kick-ball',
        'autogol' => 'mdi:arrow-u-left-top-bold',
        'sostituzione' => 'mdi:swap-horizontal-bold',
        'default' => 'mdi:help-circle-outline',
    ];

    public static function evento(string $tipo, array $dettagli = [], string $size = '1.1em'): array
    {
        $chiave = self::chiaveEvento($tipo, $dettagli);

        return [
            'key' => $chiave,
            'label' => self::labelEvento($chiave, $tipo),
            'icon' => self::icon($chiave, self::classiEvento($chiave), $size),
            'classes' => self::classiEvento($chiave),
        ];
    }

    public static function assist(?string $assist, string $size = '1em'): string
    {
        $assist = trim((string) $assist);

        if ($assist === '') {
            return '';
        }

        return sprintf(
            '<div class="small text-muted mt-1">%s Assist: %s</div>',
            self::icon('assist', 'me-1 text-muted', $size),
            htmlspecialchars($assist, ENT_QUOTES, 'UTF-8')
        );
    }

    public static function icon(string $chiave, string $classi = '', string $size = '1.1em'): string
    {
        $icon = self::EVENT_ICONS[$chiave] ?? self::EVENT_ICONS['default'];

        $classAttr = trim($classi);
        $classHtml = $classAttr !== '' ? ' class="' . htmlspecialchars($classAttr, ENT_QUOTES, 'UTF-8') . '"' : '';

        return sprintf(
            '<iconify-icon inline icon="%s" width="%s"%s aria-hidden="true"></iconify-icon>',
            htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($size, ENT_QUOTES, 'UTF-8'),
            $classHtml
        );
    }

    private static function chiaveEvento(string $tipo, array $dettagli = []): string
    {
        if ($tipo === 'gol') {
            if (!empty($dettagli['autogol'])) {
                return 'autogol';
            }

            if (!empty($dettagli['rigore'])) {
                return 'gol_rigore';
            }

            return 'gol';
        }

        return match ($tipo) {
            'rigore_sbagliato' => 'rigore_sbagliato',
            'ammonizione' => 'ammonizione',
            'espulsione' => 'espulsione',
            'sostituzione' => 'sostituzione',
            default => 'default',
        };
    }

    private static function labelEvento(string $chiave, string $tipo): string
    {
        return match ($chiave) {
            'gol' => 'Gol',
            'gol_rigore' => 'Gol su rigore',
            'rigore_sbagliato' => 'Rigore sbagliato',
            'ammonizione' => 'Ammonizione',
            'espulsione' => 'Espulsione',
            'autogol' => 'Autogol',
            'sostituzione' => 'Sostituzione',
            default => ucfirst(str_replace('_', ' ', $tipo)),
        };
    }

    private static function classiEvento(string $chiave): string
    {
        return match ($chiave) {
            'gol_rigore' => 'text-primary',
            'rigore_sbagliato' => 'text-warning',
            'ammonizione' => 'text-warning',
            'espulsione' => 'text-danger',
            'autogol' => 'text-danger',
            'sostituzione' => 'text-success',
            default => '',
        };
    }
}
