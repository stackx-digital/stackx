<?php

namespace App\Services\Ingest;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Tolerant value normalization for Meta CSV cells. Meta mixes currency
 * symbols, thousands separators, percent signs, and locale quirks; blanks show
 * up as "", "-", "—", or "N/A". Anything unparseable becomes null — never a
 * fabricated 0 (§3).
 */
class MetaValue
{
    private const BLANKS = ['', '-', '–', '—', 'n/a', 'na', 'null'];

    /** Parse a decimal/currency/percent cell → float|null. */
    public static function number(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        $s = Str::of((string) $raw)->trim()->lower()->value();

        if (in_array($s, self::BLANKS, true)) {
            return null;
        }

        // Strip currency codes/symbols, percent, thousands separators, spaces.
        $s = preg_replace('/(rm|myr|usd|\$|%)/i', '', (string) $raw);
        $s = str_replace([',', ' ', "\u{00a0}"], '', (string) $s);
        $s = trim($s);

        if (! is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    /** Parse an integer count cell → int|null. */
    public static function integer(mixed $raw): ?int
    {
        $n = self::number($raw);

        return $n === null ? null : (int) round($n);
    }

    /** Parse a date cell → Y-m-d string, or null for lifetime rows. */
    public static function date(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $s = trim((string) $raw);

        if ($s === '' || in_array(Str::lower($s), self::BLANKS, true)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($s)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
