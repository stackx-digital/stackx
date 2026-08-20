<?php

namespace App\Services\Scoring;

/**
 * Percentile-rank helper (§3). Scores are relative to the ad account, so each
 * raw metric is turned into a 0–100 percentile within the set. Uses a mid-rank
 * (average-rank) formula so ties share a percentile and the distribution
 * spreads honestly.
 */
class Percentile
{
    /**
     * Rank a map of id => value into id => percentile (0–100).
     *
     * @param  array<int|string, float>  $values  non-null raw values
     * @param  bool  $higherIsBetter  false for cost-style metrics (lower = better)
     * @return array<int|string, int>
     */
    public static function rank(array $values, bool $higherIsBetter = true): array
    {
        $n = count($values);

        if ($n === 0) {
            return [];
        }

        // A single ad has no distribution to rank against — neutral midpoint.
        if ($n === 1) {
            return [array_key_first($values) => 50];
        }

        $result = [];

        foreach ($values as $id => $v) {
            $better = 0; // count strictly "worse" than v (so v ranks above them)
            $equal = 0;

            foreach ($values as $v2) {
                if ($v2 === $v) {
                    $equal++;

                    continue;
                }

                $v2IsWorse = $higherIsBetter ? ($v2 < $v) : ($v2 > $v);

                if ($v2IsWorse) {
                    $better++;
                }
            }

            $pct = ($better + 0.5 * $equal) / $n * 100;
            $result[$id] = (int) round($pct);
        }

        return $result;
    }
}
