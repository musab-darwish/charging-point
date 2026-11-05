<?php

namespace App\Support;

class Numbers
{
    /**
     * Turn a number typed on an Arabic keyboard (١٫٥ or ١,٥) into one PHP
     * and the database understand (1.5). Returns null for empty input.
     */
    public static function normalize(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '٫' => '.', ',' => '.', '،' => '.',
        ]);

        return preg_replace('/\s+/', '', $value);
    }
}
