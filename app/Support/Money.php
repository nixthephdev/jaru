<?php

namespace App\Support;

class Money
{
    /** 12000 -> "₱120", 12050 -> "₱120.50"; null -> "". */
    public static function peso(?int $cents): string
    {
        return $cents === null ? '' : '₱'.number_format($cents / 100, $cents % 100 ? 2 : 0);
    }

    /** "120", "120.50", "₱1,200" -> centavos; "" -> null. */
    public static function parse(?string $s): ?int
    {
        $s = trim(str_replace([',', '₱', ' '], '', (string) $s));

        return $s === '' ? null : (int) round((float) $s * 100);
    }

    /** "12oz ₱120 · 16oz ₱140" */
    public static function priceText(array $rows): string
    {
        $out = [];
        foreach ($rows as $p) {
            if (($p['cents'] ?? null) === null) {
                continue;
            }
            $out[] = trim(($p['label'] ?? '').' '.self::peso($p['cents']));
        }

        return implode(' · ', $out);
    }
}
