<?php

namespace App\Support;

/**
 * Single place for egg arithmetic and the legacy "11 Cr 12PC" text format.
 *
 * Eggs are stored as integer pieces in the database. Crates/pieces strings
 * are only produced for display (and, during the transition, for the legacy
 * text columns that are still written for backward compatibility).
 */
class Eggs
{
    public const PER_CRATE = 30;

    /**
     * Convert a legacy egg string to total pieces.
     *
     * Accepted shapes: "11 Cr 12PC", "11 Cr 12 PC", "11 CR", "1,234 Cr",
     * "5741 Cr 2PC" (case-insensitive), a bare number (treated as pieces),
     * null/empty (0).
     */
    public static function parse(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value)) {
            return max(0, $value);
        }

        if (is_float($value) || (is_string($value) && preg_match('/^\s*\d+(\.\d+)?\s*$/', $value))) {
            return max(0, (int) $value);
        }

        if (! is_string($value)) {
            return 0;
        }

        $crates = 0;
        $pieces = 0;

        if (preg_match('/([\d,]+)\s*cr/i', $value, $m)) {
            $crates = (int) str_replace(',', '', $m[1]);
        }

        if (preg_match('/(\d+)\s*pc/i', $value, $m)) {
            $pieces = (int) $m[1];
        }

        return $crates * self::PER_CRATE + $pieces;
    }

    /**
     * Format total pieces as "11 Cr 12PC".
     */
    public static function format(int|float|null $totalPieces): string
    {
        $totalPieces = max(0, (int) $totalPieces);

        return intdiv($totalPieces, self::PER_CRATE) . ' Cr ' . ($totalPieces % self::PER_CRATE) . 'PC';
    }

    public static function crates(int|float|null $totalPieces): int
    {
        return intdiv(max(0, (int) $totalPieces), self::PER_CRATE);
    }

    public static function remainder(int|float|null $totalPieces): int
    {
        return max(0, (int) $totalPieces) % self::PER_CRATE;
    }

    public static function toPieces(int $crates, int $pieces = 0): int
    {
        return $crates * self::PER_CRATE + $pieces;
    }
}
