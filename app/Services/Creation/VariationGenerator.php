<?php

namespace App\Services\Creation;

use App\Models\Ad;
use App\Models\AdVariation;
use App\Models\CompetitorAd;
use App\Services\Ai\Exceptions\AiException;
use App\Support\Facades\Ai;
use Illuminate\Support\Str;

/**
 * Generates ad copy variations from a winning ad (ours or a competitor's) or a
 * free-form brief, via the AI layer (§2 P4). Output is AI-generated copy —
 * labelled as such in the UI, never a metric. Validated and persisted; failures
 * surface as AiException for the caller to degrade on.
 */
class VariationGenerator
{
    private const SYSTEM = <<<'PROMPT'
        You are a senior paid-social copywriter at a Malaysian marketing agency.
        Write high-converting Meta ad copy. %s
        Return JSON only, no prose:
        {"variations":[{"hook":"..","primary_text":"..","headline":"..","angle":"..","cta":".."}]}
        Each variation must be distinct in angle. cta is a short button label.
        PROMPT;

    private const LANGUAGES = [
        'mix' => 'Write in the natural style of Malaysian social ads: casual Bahasa Malaysia with English words mixed in (rojak/Manglish), the way real MY marketers write. Punchy and scroll-stopping.',
        'bm' => 'Write fully in Bahasa Malaysia, casual and persuasive.',
        'en' => 'Write fully in English, punchy and modern.',
    ];

    /**
     * @param  array{source?:?string, source_id?:?int, product:string, tone?:?string, count?:int, language?:string, created_by?:?int}  $input
     *
     * @throws AiException
     */
    public function generate(array $input): AdVariation
    {
        $count = max(1, min(8, (int) ($input['count'] ?? 5)));
        $language = $input['language'] ?? 'mix';
        if (! array_key_exists($language, self::LANGUAGES)) {
            $language = 'mix';
        }
        [$sourceType, $sourceId, $reference] = $this->resolveSource($input);

        $system = sprintf(self::SYSTEM, self::LANGUAGES[$language]);
        $user = $this->userPrompt($input['product'], $input['tone'] ?? null, $count, $reference);

        $response = Ai::structuredJson($system, $user);
        $variations = $this->validate($response['variations'] ?? [], $count);

        return AdVariation::create([
            'source' => $sourceType,
            'source_id' => $sourceId,
            'product' => $input['product'],
            'prompt' => $user,
            'output' => $variations,
            'generated_by' => $this->generatedBy(),
            'created_by' => $input['created_by'] ?? null,
        ]);
    }

    /** @return array{0:?string, 1:?int, 2:?string} [sourceType, sourceId, referenceText] */
    private function resolveSource(array $input): array
    {
        $type = $input['source'] ?? null;
        $id = $input['source_id'] ?? null;

        if ($type === 'ad' && $id) {
            $ad = Ad::with(['score', 'tags'])->find($id);
            if ($ad) {
                $bits = array_filter([
                    "Winning ad: \"{$ad->name}\"",
                    $ad->score?->action ? "action: {$ad->score->action}" : null,
                    $ad->tags?->angle ? "angle: {$ad->tags->angle}" : null,
                ]);

                return ['ad', (int) $id, implode(' · ', $bits)];
            }
        }

        if ($type === 'competitor_ad' && $id) {
            $ca = CompetitorAd::with('competitor')->find($id);
            if ($ca) {
                return ['competitor_ad', (int) $id, "Competitor ad ({$ca->competitor?->name}): \"".Str::limit($ca->body, 300)."\""];
            }
        }

        return [null, null, null];
    }

    private function userPrompt(string $product, ?string $tone, int $count, ?string $reference): string
    {
        $lines = ["Product / offer: {$product}"];
        if ($tone) {
            $lines[] = "Tone: {$tone}";
        }
        if ($reference) {
            $lines[] = "Base it on this proven creative — keep what works, vary the angle: {$reference}";
        }
        $lines[] = "Generate {$count} distinct variations.";

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param  mixed  $items
     * @return array<int, array{hook:?string, primary_text:?string, headline:?string, angle:?string, cta:?string}>
     */
    private function validate(mixed $items, int $count): array
    {
        if (! is_array($items)) {
            return [];
        }

        $str = fn ($v) => is_string($v) && trim($v) !== '' ? trim($v) : null;
        $out = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $variation = [
                'hook' => $str($item['hook'] ?? null),
                'primary_text' => $str($item['primary_text'] ?? null),
                'headline' => $str($item['headline'] ?? null),
                'angle' => $str($item['angle'] ?? null),
                'cta' => $str($item['cta'] ?? null),
            ];

            // Keep only variations with real copy.
            if ($variation['hook'] || $variation['primary_text']) {
                $out[] = $variation;
            }

            if (count($out) >= $count) {
                break;
            }
        }

        return $out;
    }

    private function generatedBy(): string
    {
        try {
            $driver = Ai::driver();

            return $driver->name().':'.$driver->model();
        } catch (\Throwable) {
            return 'ai';
        }
    }
}
