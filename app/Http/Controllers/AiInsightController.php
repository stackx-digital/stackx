<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Services\Recommendation\RecommendationGenerator;
use App\Services\Recommendation\RecommendationInput;
use App\Services\Scoring\MetricAggregator;
use App\Services\Tagging\AdTagger;
use App\Services\Tagging\TaggableAd;
use Illuminate\Http\RedirectResponse;

/**
 * Generates AI insights (§3, M5): infers creative tags for every ad and writes
 * the strategic reasoning for scored ads. Both are best-effort — if the AI
 * layer is unconfigured or fails, analytics is untouched and the user is told
 * plainly. Nothing here changes the deterministic scores.
 */
class AiInsightController extends Controller
{
    public function store(
        MetricAggregator $aggregator,
        AdTagger $tagger,
        RecommendationGenerator $recommendations,
    ): RedirectResponse {
        $ads = Ad::with(['score', 'metrics'])->get();

        if ($ads->isEmpty()) {
            return redirect()->route('analytics')
                ->with('status', 'No ads to analyse — import data or load the demo first.');
        }

        // 1. Infer creative tags from ad names (pluggable to vision in Phase 2).
        $tagResult = $tagger->tag($ads->map(fn (Ad $ad) => TaggableAd::fromAd($ad)));

        // 2. Strategic reasoning, layered on the deterministic action for scored ads.
        $aggregates = $aggregator->aggregate($ads);
        $inputs = $ads->filter(fn (Ad $ad) => $ad->score !== null)->map(function (Ad $ad) use ($aggregates) {
            $agg = $aggregates[$ad->id] ?? null;
            $s = $ad->score;

            return new RecommendationInput(
                adId: $ad->id,
                name: $ad->name,
                action: $s->action,
                hook: $s->hook,
                watch: $s->watch,
                click: $s->click,
                convert: $s->convert,
                roas: $agg && $agg->roas !== null ? round($agg->roas, 2) : null,
                spend: $agg?->spend,
            );
        });
        $recResult = $recommendations->generate($inputs);

        return redirect()->route('analytics')->with('status', $this->status($tagResult, $recResult));
    }

    private function status($tagResult, $recResult): string
    {
        $ranAtAll = $tagResult->tagged > 0 || $recResult->written > 0;

        if (! $ranAtAll && ($tagResult->failedBatches > 0 || $recResult->failedBatches > 0)) {
            $hint = $tagResult->errors[0] ?? $recResult->errors[0] ?? 'AI provider not configured.';

            return 'AI insights did not run — '.$hint.' (check AI_PROVIDER and the API key).';
        }

        return sprintf(
            'AI insights updated — %d ad(s) tagged, %d recommendation(s) written.',
            $tagResult->tagged,
            $recResult->written,
        );
    }
}
