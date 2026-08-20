<?php

namespace App\Services\Recommendation;

use App\Models\AdScore;
use App\Services\Ai\Exceptions\AiException;
use App\Support\Facades\Ai;
use Illuminate\Support\Str;
use Throwable;

/**
 * Generates the strategic "why scale / why cut" text via the AI layer (§3, M5),
 * layered on top of the deterministic action — it explains, never overrides.
 * Batched, validated, and non-fatal on failure. The text is stored on
 * ad_scores.ai_recommendation and always shown as inferred in the UI.
 */
class RecommendationGenerator
{
    private const BATCH_SIZE = 8;

    private const SYSTEM = <<<'PROMPT'
        You are a senior media buyer at a Malaysian agency. For each ad you are
        given its deterministic action (scale/keep/cut) and its funnel percentile
        scores (hook/watch/click/convert, 0-100, null = N/A) plus ROAS and spend
        in RM. Write ONE concise sentence (max 28 words) explaining WHY that
        action makes sense, naming the funnel stage that drives it. Do not
        contradict or change the action. Return JSON only:
        {"recommendations":[{"i":<index>,"text":"..."}]}
        PROMPT;

    /**
     * @param  iterable<RecommendationInput>  $inputs
     */
    public function generate(iterable $inputs): RecommendationResult
    {
        $result = new RecommendationResult;
        $by = $this->inferredBy();

        foreach (collect($inputs)->chunk(self::BATCH_SIZE) as $batch) {
            $result->batches++;
            $indexed = $batch->values();

            try {
                $response = Ai::structuredJson(self::SYSTEM, $this->userPrompt($indexed));
                $this->persist($indexed, $response, $by, $result);
            } catch (AiException|Throwable $e) {
                $result->failed($e->getMessage());
            }
        }

        return $result;
    }

    /** @param \Illuminate\Support\Collection<int, RecommendationInput> $batch */
    private function userPrompt($batch): string
    {
        $ads = $batch->map(fn (RecommendationInput $in, int $i) => $in->toPromptArray($i))->all();

        return 'Explain the action for these ads:'.PHP_EOL.json_encode(['ads' => $ads], JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, RecommendationInput>  $batch
     * @param  array<string, mixed>  $response
     */
    private function persist($batch, array $response, string $by, RecommendationResult $result): void
    {
        $items = $response['recommendations'] ?? [];

        if (! is_array($items)) {
            $result->failed('AI response missing "recommendations" array.');

            return;
        }

        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['i']) || ! is_numeric($item['i'])) {
                continue;
            }

            $text = isset($item['text']) && is_string($item['text']) ? trim($item['text']) : '';
            if ($text === '') {
                continue;
            }

            $input = $batch->get((int) $item['i']);
            if (! $input instanceof RecommendationInput) {
                continue;
            }

            $updated = AdScore::where('ad_id', $input->adId)->update([
                'ai_recommendation' => Str::limit($text, 280, ''),
                'ai_recommendation_by' => $by,
            ]);

            if ($updated > 0) {
                $result->written++;
            }
        }
    }

    private function inferredBy(): string
    {
        try {
            $driver = Ai::driver();

            return $driver->name().':'.$driver->model();
        } catch (Throwable) {
            return 'ai';
        }
    }
}
