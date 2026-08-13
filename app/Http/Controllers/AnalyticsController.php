<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Services\Scoring\MetricAggregator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P1 Creative Analytics report. Loads scored ads with their aggregated spend
 * and ROAS for display. Scores come from ad_scores (computed deterministically
 * in M3); the full sortable table + winners/losers + detail drawer land in M4.
 */
class AnalyticsController extends Controller
{
    public function index(MetricAggregator $aggregator): Response
    {
        $ads = Ad::with(['score', 'adAccount', 'metrics'])->get();
        $aggregates = $aggregator->aggregate($ads);

        $rows = $ads->map(function (Ad $ad) use ($aggregates) {
            $agg = $aggregates[$ad->id] ?? null;
            $score = $ad->score;

            return [
                'id' => $ad->id,
                'name' => $ad->name,
                'account' => $ad->adAccount?->name,
                'spend' => $agg?->spend,
                'roas' => $agg && $agg->roas !== null ? round($agg->roas, 2) : null,
                'scores' => $score ? [
                    'hook' => $score->hook,
                    'watch' => $score->watch,
                    'click' => $score->click,
                    'convert' => $score->convert,
                ] : null,
                'action' => $score?->action,
                'actionReason' => $score?->action_reason,
            ];
        })->sortByDesc('spend')->values();

        $totalSpend = collect($aggregates)->sum(fn ($a) => $a->spend);
        $totalRevenue = collect($aggregates)->sum(fn ($a) => $a->revenue ?? 0);
        $totalResults = collect($aggregates)->sum(fn ($a) => $a->results ?? 0);

        return Inertia::render('Analytics/Index', [
            'ads' => $rows,
            'summary' => [
                'adCount' => $ads->count(),
                'totalSpend' => round($totalSpend, 2),
                'blendedRoas' => $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : null,
                'totalResults' => $totalResults > 0 ? round($totalResults, 0) : null,
                'scored' => $ads->filter(fn ($a) => $a->score !== null)->count(),
            ],
        ]);
    }
}
