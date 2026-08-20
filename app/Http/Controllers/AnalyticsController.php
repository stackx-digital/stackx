<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Services\Scoring\AdAggregate;
use App\Services\Scoring\MetricAggregator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P1 Creative Analytics report (M4). Account summary + scored ad table with
 * winners/losers. Per-ad detail (daily trend) is fetched on demand by the
 * drawer via AdController@show. Scores are the deterministic ad_scores (M3).
 */
class AnalyticsController extends Controller
{
    public function index(MetricAggregator $aggregator): Response
    {
        $ads = Ad::with(['score', 'tags', 'adAccount', 'metrics'])->get();
        $aggregates = $aggregator->aggregate($ads);

        $rows = $ads->map(function (Ad $ad) use ($aggregates) {
            $agg = $aggregates[$ad->id] ?? null;

            return $this->row($ad, $agg);
        })->sortByDesc('spend')->values();

        return Inertia::render('Analytics/Index', [
            'ads' => $rows,
            'summary' => $this->summary($ads, $aggregates),
        ]);
    }

    /** @param  array<int, AdAggregate>  $aggregates */
    private function summary($ads, array $aggregates): array
    {
        $totalSpend = collect($aggregates)->sum(fn ($a) => $a->spend);
        $totalRevenue = collect($aggregates)->sum(fn ($a) => $a->revenue ?? 0);
        $totalResults = collect($aggregates)->sum(fn ($a) => $a->results ?? 0);

        return [
            'adCount' => $ads->count(),
            'scored' => $ads->filter(fn ($a) => $a->score !== null)->count(),
            'totalSpend' => round($totalSpend, 2),
            'blendedRoas' => $totalRevenue > 0 && $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : null,
            'blendedCpa' => $totalResults > 0 && $totalSpend > 0 ? round($totalSpend / $totalResults, 2) : null,
            'totalResults' => $totalResults > 0 ? round($totalResults, 0) : null,
        ];
    }

    private function row(Ad $ad, ?AdAggregate $agg): array
    {
        $score = $ad->score;

        return [
            'id' => $ad->id,
            'name' => $ad->name,
            'account' => $ad->adAccount?->name,
            'status' => $ad->status,
            'thumbnailUrl' => $ad->thumbnail_url,
            'spend' => $agg?->spend,
            'impressions' => $agg?->impressions,
            'roas' => $agg && $agg->roas !== null ? round($agg->roas, 2) : null,
            'cpr' => $agg && $agg->costPerResult !== null ? round($agg->costPerResult, 2) : null,
            'ctrLink' => $agg && $agg->linkCtr !== null ? round($agg->linkCtr, 2) : null,
            'results' => $agg && $agg->results !== null ? round($agg->results, 0) : null,
            'scores' => $score ? [
                'hook' => $score->hook,
                'watch' => $score->watch,
                'click' => $score->click,
                'convert' => $score->convert,
            ] : null,
            'action' => $score?->action,
            'actionReason' => $score?->action_reason,
            'aiRecommendation' => $score?->ai_recommendation,
            'tags' => $ad->tags ? [
                'format' => $ad->tags->format,
                'hookType' => $ad->tags->hook_type,
                'angle' => $ad->tags->angle,
                'audience' => $ad->tags->audience,
                'inferredBy' => $ad->tags->inferred_by,
            ] : null,
        ];
    }
}
