<?php

namespace App\Services\Recommendation;

/**
 * Context handed to the AI for one ad's strategic reasoning. It carries the
 * deterministic outputs (action + funnel scores + headline metrics); the AI
 * explains them — it never recomputes or overrides them (§3).
 */
class RecommendationInput
{
    public function __construct(
        public readonly int $adId,
        public readonly string $name,
        public readonly ?string $action,
        public readonly ?int $hook,
        public readonly ?int $watch,
        public readonly ?int $click,
        public readonly ?int $convert,
        public readonly ?float $roas,
        public readonly ?float $spend,
    ) {}

    /** @return array<string, mixed> */
    public function toPromptArray(int $i): array
    {
        return [
            'i' => $i,
            'name' => $this->name,
            'action' => $this->action,
            'hook' => $this->hook,
            'watch' => $this->watch,
            'click' => $this->click,
            'convert' => $this->convert,
            'roas' => $this->roas,
            'spend' => $this->spend,
        ];
    }
}
