<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Accepts the ways Kenyans write numbers (0712..., 712..., +254712..., 254 712 ...)
     * and returns +2547XXXXXXXX or +2541XXXXXXXX, or null when it is not a Kenyan mobile.
     */
    public static function normalise(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = preg_replace('/[\s\-().]/', '', trim($input));

        if (! preg_match('/^(?:\+?254|0)?([17]\d{8})$/', (string) $digits, $m)) {
            return null;
        }

        return '+254'.$m[1];
    }

    /**
     * Shows only the last three digits, for screens that confirm which number got the code.
     */
    public static function mask(string $normalised): string
    {
        return '+254 *** *** '.substr($normalised, -3);
    }
}
