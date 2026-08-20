<?php

namespace App\Services\Scoring;

/**
 * An ad's metrics aggregated across its daily rows into the canonical raw rates
 * the scoring engine ranks on. Any rate is null when its source metric is
 * absent for the ad — never fabricated (§3).
 */
class AdAggregate
{
    public function __construct(
        public readonly int $adId,
        public readonly float $spend,
        public readonly int $impressions,
        public readonly ?float $hookRate,   // 3s video plays / impressions
        public readonly ?float $holdRate,   // thruplays / impressions
        public readonly ?float $linkCtr,    // link clicks / impressions (%)
        public readonly ?float $ctrAll,     // all clicks / impressions (%)
        public readonly ?float $roas,       // revenue / spend
        public readonly ?float $costPerResult,
        public readonly ?float $revenue,
        public readonly ?float $results,
    ) {}

    public function metric(string $name): ?float
    {
        return match ($name) {
            'hook_rate' => $this->hookRate,
            'hold_rate' => $this->holdRate,
            'link_ctr' => $this->linkCtr,
            'ctr_all' => $this->ctrAll,
            'roas' => $this->roas,
            'cost_per_result' => $this->costPerResult,
            default => null,
        };
    }
}
