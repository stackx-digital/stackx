<?php

namespace App\Services\Scoring;

/**
 * The computed scores for one ad: four funnel percentiles (0–100 or null for
 * N/A) plus the deterministic action. All computed in PHP — never AI (§3).
 */
class ScoreResult
{
    public function __construct(
        public readonly int $adId,
        public ?int $hook = null,
        public ?int $watch = null,
        public ?int $click = null,
        public ?int $convert = null,
        public ?string $action = null,
        public ?string $actionReason = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'ad_id' => $this->adId,
            'hook' => $this->hook,
            'watch' => $this->watch,
            'click' => $this->click,
            'convert' => $this->convert,
            'action' => $this->action,
            'action_reason' => $this->actionReason,
        ];
    }
}
