<?php

namespace App\Services\Reporting;

use App\Models\Ad;
use App\Models\Competitor;
use App\Services\Scoring\MetricAggregator;

/**
 * Snapshots the current analytics into a self-contained payload for a shareable
 * report (§5, P5): account summary, winners/losers, and the competitors'
 * longest-running ads. Everything here is already-computed data — the snapshot
 * just freezes it at a point in time.
 */
class ReportBuilder
{
    public function __construct(private readonly MetricAggregator $aggregator) {}

    /** @return array<string, mixed> */
    public function build(): array
    {
        $ads = Ad::with(['score', 'adAccount', 'metrics'])->get();
        $aggregates = $this->aggregator->aggregate($ads);

        $rows = $ads->map(function (Ad $ad) use ($aggregates) {
            $agg = $aggregates[$ad->id] ?? null;
            $s = $ad->score;

            return [
                'name' => $ad->name,
                'account' => $ad->adAccount?->name,
                'spend' => $agg?->spend,
                'roas' => $agg && $agg->roas !== null ? round($agg->roas, 2) : null,
                'scores' => $s ? ['hook' => $s->hook, 'watch' => $s->watch, 'click' => $s->click, 'convert' => $s->convert] : null,
                'action' => $s?->action,
                'actionReason' => $s?->action_reason,
            ];
        });

        $totalSpend = collect($aggregates)->sum(fn ($a) => $a->spend);
        $totalRevenue = collect($aggregates)->sum(fn ($a) => $a->revenue ?? 0);
        $totalResults = collect($aggregates)->sum(fn ($a) => $a->results ?? 0);

        return [
            'generatedAt' => now()->toDayDateTimeString(),
            'summary' => [
                'adCount' => $ads->count(),
                'totalSpend' => round($totalSpend, 2),
                'blendedRoas' => $totalRevenue > 0 && $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : null,
                'blendedCpa' => $totalResults > 0 && $totalSpend > 0 ? round($totalSpend / $totalResults, 2) : null,
                'totalResults' => $totalResults > 0 ? round($totalResults, 0) : null,
            ],
            'winners' => $rows->where('action', 'scale')->sortByDesc('roas')->take(5)->values()->all(),
            'losers' => $rows->where('action', 'cut')->sortBy('roas')->take(5)->values()->all(),
            'topCompetitorAds' => $this->topCompetitorAds(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function topCompetitorAds(): array
    {
        return Competitor::with(['ads' => fn ($q) => $q->orderByDesc('days_running')])
            ->get()
            ->flatMap(fn (Competitor $c) => $c->ads->take(3)->map(fn ($ad) => [
                'competitor' => $c->name,
                'body' => \Illuminate\Support\Str::limit($ad->body, 120),
                'daysRunning' => $ad->days_running,
            ]))
            ->sortByDesc('daysRunning')
            ->take(5)
            ->values()
            ->all();
    }
}
