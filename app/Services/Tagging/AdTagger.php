<?php

namespace App\Services\Tagging;

use App\Models\AdTag;
use App\Services\Ai\Exceptions\AiException;
use App\Support\Facades\Ai;
use Illuminate\Support\Str;
use Throwable;

/**
 * Infers creative tags (format/hook_type/angle/audience) via the AI layer (§3,
 * M5). Ads are batched (~8/request) to control cost/latency; each batch is
 * validated and upserted. AI failures are non-fatal — analytics works without
 * tags. Tags are always labelled "inferred" in the UI; nothing here touches the
 * deterministic scores.
 */
class AdTagger
{
    private const BATCH_SIZE = 8;

    private const SYSTEM = <<<'PROMPT'
        You are a paid-social creative analyst for a Malaysian marketing agency.
        Classify each ad from its name. Prefer this vocabulary but use your own
        word when none fits:
        - format: video | image | carousel | reels | unknown
        - hook_type: question | discount | curiosity | problem-solution | testimonial | ugc | urgency | offer | other
        - angle: savings | seasonal | urgency | social-proof | product-benefit | giveaway | other
        - audience: general | parents | youth | shoppers | existing-customers | other
        Return JSON only, shape:
        {"tags":[{"i":<index>,"format":"..","hook_type":"..","angle":"..","audience":"..","confidence":0.0-1.0}]}
        PROMPT;

    /**
     * @param  iterable<TaggableAd>  $ads
     */
    public function tag(iterable $ads): TaggingResult
    {
        $result = new TaggingResult;
        $inferredBy = $this->inferredBy();

        foreach (collect($ads)->chunk(self::BATCH_SIZE) as $batch) {
            $result->batches++;
            $indexed = $batch->values(); // 0..n within the batch

            try {
                $response = Ai::structuredJson(self::SYSTEM, $this->userPrompt($indexed));
                $this->persist($indexed, $response, $inferredBy, $result);
            } catch (AiException|Throwable $e) {
                $result->failed($e->getMessage());
            }
        }

        return $result;
    }

    /** @param \Illuminate\Support\Collection<int, TaggableAd> $batch */
    private function userPrompt($batch): string
    {
        $ads = $batch->map(fn (TaggableAd $a, int $i) => ['i' => $i, 'name' => $a->name])->all();

        return 'Classify these ads:'.PHP_EOL.json_encode(['ads' => $ads], JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TaggableAd>  $batch
     * @param  array<string, mixed>  $response
     */
    private function persist($batch, array $response, string $inferredBy, TaggingResult $result): void
    {
        $items = $response['tags'] ?? [];

        if (! is_array($items)) {
            $result->failed('AI response missing "tags" array.');

            return;
        }

        foreach ($items as $item) {
            $tag = $this->validate($item);
            if ($tag === null) {
                continue;
            }

            $ad = $batch->get($tag['i']);
            if (! $ad instanceof TaggableAd) {
                continue;
            }

            AdTag::updateOrCreate(
                ['ad_id' => $ad->adId],
                [
                    'format' => $tag['format'],
                    'hook_type' => $tag['hook_type'],
                    'angle' => $tag['angle'],
                    'audience' => $tag['audience'],
                    'inferred_by' => $inferredBy,
                    'confidence' => $tag['confidence'],
                ],
            );

            $result->tagged++;
        }
    }

    /**
     * Validate + normalize one AI item. Returns null if the index is unusable.
     *
     * @param  mixed  $item
     * @return array{i:int, format:?string, hook_type:?string, angle:?string, audience:?string, confidence:?float}|null
     */
    private function validate(mixed $item): ?array
    {
        if (! is_array($item) || ! isset($item['i']) || ! is_numeric($item['i'])) {
            return null;
        }

        $string = fn ($v) => is_string($v) && trim($v) !== '' ? Str::of($v)->lower()->trim()->limit(40, '')->value() : null;
        $confidence = isset($item['confidence']) && is_numeric($item['confidence'])
            ? max(0.0, min(1.0, (float) $item['confidence']))
            : null;

        return [
            'i' => (int) $item['i'],
            'format' => $string($item['format'] ?? null),
            'hook_type' => $string($item['hook_type'] ?? null),
            'angle' => $string($item['angle'] ?? null),
            'audience' => $string($item['audience'] ?? null),
            'confidence' => $confidence,
        ];
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
