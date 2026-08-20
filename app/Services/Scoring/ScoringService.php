<?php

namespace App\Services\Scoring;

use App\Models\AdAccount;
use App\Models\AdScore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates the deterministic scoring pipeline (§3): aggregate daily metrics
 * → percentile funnel scores → spend-weighted action → persist to ad_scores.
 * All maths is deterministic and computed in PHP; nothing here calls the AI
 * layer.
 */
class ScoringService
{
    public function __construct(
        private readonly MetricAggregator $aggregator = new MetricAggregator,
        private readonly ScoreCalculator $calculator = new ScoreCalculator,
        private readonly ActionClassifier $classifier = new ActionClassifier,
    ) {}

    /** @return array<int, ScoreResult> */
    public function computeForAccount(AdAccount $account): array
    {
        return $this->compute($this->aggregator->forAccount($account));
    }

    /**
     * @param  array<int, AdAggregate>  $aggregates
     * @return array<int, ScoreResult>
     */
    public function compute(array $aggregates): array
    {
        $funnel = $this->calculator->funnelScores($aggregates);
        $actions = $this->classifier->classify($aggregates);

        $results = [];
        foreach ($aggregates as $id => $_) {
            $results[$id] = new ScoreResult(
                adId: $id,
                hook: $funnel[$id]['hook'] ?? null,
                watch: $funnel[$id]['watch'] ?? null,
                click: $funnel[$id]['click'] ?? null,
                convert: $funnel[$id]['convert'] ?? null,
                action: $actions[$id]['action'] ?? null,
                actionReason: $actions[$id]['reason'] ?? null,
            );
        }

        return $results;
    }

    /**
     * Compute and persist. Returns the number of ads scored.
     */
    public function scoreAccount(AdAccount $account): int
    {
        $results = $this->computeForAccount($account);
        $now = Carbon::now();

        DB::transaction(function () use ($results, $now) {
            foreach ($results as $result) {
                AdScore::updateOrCreate(
                    ['ad_id' => $result->adId],
                    array_merge($result->toArray(), ['computed_at' => $now]),
                );
            }
        });

        return count($results);
    }
}
