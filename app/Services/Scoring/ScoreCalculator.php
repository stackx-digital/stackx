<?php

namespace App\Services\Scoring;

/**
 * Computes the four funnel scores (§3) as percentile ranks within the account,
 * with account-wide fallback chains:
 *   Hook    ← 3s-plays/impressions, fallback CTR (all)
 *   Watch   ← ThruPlays/impressions, fallback Hook Score
 *   Click   ← link CTR
 *   Convert ← ROAS, fallback inverse cost-per-result
 *
 * A stage is N/A (null) for an ad when the chosen metric is absent for that ad,
 * and N/A for everyone when the metric (and its fallback) is missing across the
 * whole account. Nothing is fabricated.
 */
class ScoreCalculator
{
    /**
     * @param  array<int, AdAggregate>  $aggregates
     * @return array<int, array{hook:?int, watch:?int, click:?int, convert:?int}>
     */
    public function funnelScores(array $aggregates): array
    {
        // Hook: primary 3s-play rate, else all-CTR (account-wide fallback).
        $hookMetric = $this->available($aggregates, 'hook_rate')
            ? 'hook_rate'
            : ($this->available($aggregates, 'ctr_all') ? 'ctr_all' : null);
        $hook = $hookMetric ? $this->rankMetric($aggregates, $hookMetric) : [];

        // Watch: primary hold rate, else copy the Hook score.
        $watch = $this->available($aggregates, 'hold_rate')
            ? $this->rankMetric($aggregates, 'hold_rate')
            : $hook;

        // Click: link CTR.
        $click = $this->available($aggregates, 'link_ctr')
            ? $this->rankMetric($aggregates, 'link_ctr')
            : [];

        // Convert: ROAS (higher better), else inverse cost-per-result.
        if ($this->available($aggregates, 'roas')) {
            $convert = $this->rankMetric($aggregates, 'roas', true);
        } elseif ($this->available($aggregates, 'cost_per_result')) {
            $convert = $this->rankMetric($aggregates, 'cost_per_result', false);
        } else {
            $convert = [];
        }

        $out = [];
        foreach ($aggregates as $id => $_) {
            $out[$id] = [
                'hook' => $hook[$id] ?? null,
                'watch' => $watch[$id] ?? null,
                'click' => $click[$id] ?? null,
                'convert' => $convert[$id] ?? null,
            ];
        }

        return $out;
    }

    /** Is a metric present for at least one ad in the account? */
    private function available(array $aggregates, string $metric): bool
    {
        foreach ($aggregates as $agg) {
            if ($agg->metric($metric) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Percentile-rank the ads that have the given metric.
     *
     * @return array<int, int>
     */
    private function rankMetric(array $aggregates, string $metric, bool $higherIsBetter = true): array
    {
        $values = [];
        foreach ($aggregates as $id => $agg) {
            $v = $agg->metric($metric);
            if ($v !== null) {
                $values[$id] = $v;
            }
        }

        return Percentile::rank($values, $higherIsBetter);
    }
}
