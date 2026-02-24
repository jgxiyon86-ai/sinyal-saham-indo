<?php

namespace App\Support;

class WaNumber
{
    public static function validationRegex(): string
    {
        // Indonesia mobile format:
        // 08xxxxxxxxxx or 628xxxxxxxxxx or +628xxxxxxxxxx
        return '/^(?:\+?62|0)8[0-9]{7,13}$/';
    }

    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($raw)) ?: '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        } elseif (str_starts_with($digits, '620')) {
            $digits = '62'.substr($digits, 3);
        }

        if (! preg_match('/^628[0-9]{7,13}$/', $digits)) {
            return null;
        }

        return $digits;
    }
}

