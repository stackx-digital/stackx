<?php

namespace App\Services\Scoring;

/**
 * Winner/loser classification (§3). Ranks by ROAS (fallback cost-per-result)
 * against a spend-weighted (blended) benchmark, and gates scale/cut on spend
 * adequacy so low-spend flukes neither top nor bottom the list. Emits a
 * threshold-based action (scale|keep|cut) with a plain-English reason. The
 * strategic "why" text (M5) layers on top of this — it never replaces it.
 */
class ActionClassifier
{
    private const SCALE_MULTIPLIER = 1.2;   // >=1.2× benchmark → scale

    private const CUT_MULTIPLIER = 0.8;     // <0.8× benchmark → cut

    private const SPEND_FLOOR_FRACTION = 0.5; // of median spend

    /**
     * @param  array<int, AdAggregate>  $aggregates
     * @return array<int, array{action:?string, reason:?string}>
     */
    public function classify(array $aggregates): array
    {
        if (empty($aggregates)) {
            return [];
        }

        $useRoas = $this->available($aggregates, 'roas');
        $useCpr = ! $useRoas && $this->available($aggregates, 'cost_per_result');

        if (! $useRoas && ! $useCpr) {
            // No conversion signal at all — can't judge outcomes honestly.
            return array_map(
                fn () => ['action' => 'keep', 'reason' => 'No ROAS or cost-per-result data — keep and gather more.'],
                $aggregates,
            );
        }

        $blended = $useRoas
            ? $this->spendWeighted($aggregates, 'roas')
            : $this->spendWeightedCpr($aggregates);

        $spendFloor = $this->median(array_map(fn ($a) => $a->spend, $aggregates)) * self::SPEND_FLOOR_FRACTION;

        $out = [];

        foreach ($aggregates as $id => $agg) {
            $out[$id] = $useRoas
                ? $this->classifyRoas($agg, $blended, $spendFloor)
                : $this->classifyCpr($agg, $blended, $spendFloor);
        }

        return $out;
    }

    /** @return array{action:string, reason:string} */
    private function classifyRoas(AdAggregate $agg, float $blended, float $spendFloor): array
    {
        $roas = $agg->roas;

        if ($roas === null) {
            return ['action' => 'keep', 'reason' => 'No ROAS for this ad — keep and gather more.'];
        }

        if ($agg->spend < $spendFloor) {
            return ['action' => 'keep', 'reason' => sprintf(
                'Spend %s below the %s judging floor — too early to call.',
                $this->rm($agg->spend), $this->rm($spendFloor),
            )];
        }

        $ratio = $blended > 0 ? $roas / $blended : 1.0;

        if ($ratio >= self::SCALE_MULTIPLIER) {
            return ['action' => 'scale', 'reason' => sprintf(
                'ROAS %.1f vs blended %.1f (%.0f%% of benchmark) on %s spend — scale.',
                $roas, $blended, $ratio * 100, $this->rm($agg->spend),
            )];
        }

        if ($ratio < self::CUT_MULTIPLIER) {
            return ['action' => 'cut', 'reason' => sprintf(
                'ROAS %.1f vs blended %.1f (%.0f%% of benchmark) — cut.',
                $roas, $blended, $ratio * 100,
            )];
        }

        return ['action' => 'keep', 'reason' => sprintf(
            'ROAS %.1f near blended %.1f — keep.', $roas, $blended,
        )];
    }

    /** @return array{action:string, reason:string} */
    private function classifyCpr(AdAggregate $agg, float $blended, float $spendFloor): array
    {
        $cpr = $agg->costPerResult;

        if ($cpr === null) {
            return ['action' => 'keep', 'reason' => 'No cost-per-result for this ad — keep and gather more.'];
        }

        if ($agg->spend < $spendFloor) {
            return ['action' => 'keep', 'reason' => sprintf(
                'Spend %s below the %s judging floor — too early to call.',
                $this->rm($agg->spend), $this->rm($spendFloor),
            )];
        }

        // Lower cost is better, so the comparison inverts.
        $ratio = $blended > 0 ? $cpr / $blended : 1.0;

        if ($ratio <= self::CUT_MULTIPLIER) {
            return ['action' => 'scale', 'reason' => sprintf(
                'Cost/result %s vs blended %s (%.0f%% of benchmark) on %s spend — scale.',
                $this->rm($cpr), $this->rm($blended), $ratio * 100, $this->rm($agg->spend),
            )];
        }

        if ($ratio > self::SCALE_MULTIPLIER) {
            return ['action' => 'cut', 'reason' => sprintf(
                'Cost/result %s vs blended %s (%.0f%% of benchmark) — cut.',
                $this->rm($cpr), $this->rm($blended), $ratio * 100,
            )];
        }

        return ['action' => 'keep', 'reason' => sprintf(
            'Cost/result %s near blended %s — keep.', $this->rm($cpr), $this->rm($blended),
        )];
    }

    private function available(array $aggregates, string $metric): bool
    {
        foreach ($aggregates as $agg) {
            if ($agg->metric($metric) !== null) {
                return true;
            }
        }

        return false;
    }

    /** Spend-weighted ROAS = total revenue / total spend. */
    private function spendWeighted(array $aggregates, string $metric): float
    {
        $revenue = 0.0;
        $spend = 0.0;

        foreach ($aggregates as $agg) {
            if ($agg->roas !== null) {
                $revenue += $agg->revenue ?? ($agg->roas * $agg->spend);
                $spend += $agg->spend;
            }
        }

        return $spend > 0 ? $revenue / $spend : 0.0;
    }

    /** Spend-weighted cost-per-result = total spend / total results. */
    private function spendWeightedCpr(array $aggregates): float
    {
        $spend = 0.0;
        $results = 0.0;

        foreach ($aggregates as $agg) {
            if ($agg->costPerResult !== null && $agg->results !== null) {
                $spend += $agg->spend;
                $results += $agg->results;
            }
        }

        return $results > 0 ? $spend / $results : 0.0;
    }

    /** @param array<int, float> $values */
    private function median(array $values): float
    {
        $values = array_values($values);
        sort($values);
        $n = count($values);

        if ($n === 0) {
            return 0.0;
        }

        $mid = intdiv($n, 2);

        return $n % 2 === 0
            ? ($values[$mid - 1] + $values[$mid]) / 2
            : $values[$mid];
    }

    private function rm(float $value): string
    {
        return 'RM'.number_format($value, $value < 100 ? 2 : 0);
    }
}
