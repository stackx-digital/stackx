<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\AdMetric;
use App\Services\Scoring\MetricAggregator;
use Illuminate\Http\JsonResponse;

/**
 * Ad detail for the analytics drawer (M4). Returns the aggregated headline
 * metrics, the deterministic score, and the daily trend. Route-model binding
 * is org-scoped by the Ad global scope, so cross-org ids 404 automatically.
 */
class AdController extends Controller
{
    public function show(Ad $ad, MetricAggregator $aggregator): JsonResponse
    {
        $ad->load(['score', 'tags', 'adAccount', 'metrics']);
        $agg = $aggregator->aggregate([$ad])[$ad->id];

        $daily = $ad->metrics
            ->sortBy('date')
            ->map(fn (AdMetric $m) => [
                'date' => $m->date->toDateString(),
                'spend' => $m->spend !== null ? (float) $m->spend : null,
                'impressions' => $m->impressions,
                'ctr_link' => $m->ctr_link !== null ? (float) $m->ctr_link : null,
                'roas' => $m->roas !== null ? (float) $m->roas : null,
                'results' => $m->results !== null ? (float) $m->results : null,
            ])
            ->values();

        return response()->json([
            'ad' => [
                'id' => $ad->id,
                'name' => $ad->name,
                'account' => $ad->adAccount?->name,
                'status' => $ad->status,
                'thumbnailUrl' => $ad->thumbnail_url,
            ],
            'aggregate' => [
                'spend' => $agg->spend,
                'impressions' => $agg->impressions,
                'roas' => $agg->roas !== null ? round($agg->roas, 2) : null,
                'cpr' => $agg->costPerResult !== null ? round($agg->costPerResult, 2) : null,
                'ctrLink' => $agg->linkCtr !== null ? round($agg->linkCtr, 2) : null,
                'results' => $agg->results !== null ? round($agg->results, 0) : null,
                'revenue' => $agg->revenue,
            ],
            'scores' => $ad->score ? [
                'hook' => $ad->score->hook,
                'watch' => $ad->score->watch,
                'click' => $ad->score->click,
                'convert' => $ad->score->convert,
            ] : null,
            'action' => $ad->score?->action,
            'actionReason' => $ad->score?->action_reason,
            'aiRecommendation' => $ad->score?->ai_recommendation,
            'aiRecommendationBy' => $ad->score?->ai_recommendation_by,
            'tags' => $ad->tags ? [
                'format' => $ad->tags->format,
                'hookType' => $ad->tags->hook_type,
                'angle' => $ad->tags->angle,
                'audience' => $ad->tags->audience,
                'inferredBy' => $ad->tags->inferred_by,
                'confidence' => $ad->tags->confidence !== null ? (float) $ad->tags->confidence : null,
            ] : null,
            'daily' => $daily,
        ]);
    }
}
