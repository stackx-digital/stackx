<?php

namespace App\Services\Tagging;

use App\Models\Ad;
use App\Models\AdTag;
use App\Support\Facades\Ai;
use Illuminate\Support\Str;

/**
 * Tags an ad from its creative IMAGE via the AI layer's vision capability (P4
 * Phase 2). Complements text-only AdTagger — same output shape, but inferred
 * from what the creative actually shows. Labelled inferred (…:vision) in the UI.
 */
class VisionTagger
{
    private const SYSTEM = <<<'PROMPT'
        You are a paid-social creative analyst for a Malaysian agency. Look at the
        ad creative image and classify it. Prefer this vocabulary; use your own
        word when none fits:
        - format: video | image | carousel | reels | unknown
        - hook_type: question | discount | curiosity | problem-solution | testimonial | ugc | urgency | offer | other
        - angle: savings | seasonal | urgency | social-proof | product-benefit | giveaway | other
        - audience: general | parents | youth | shoppers | existing-customers | other
        Return JSON only:
        {"format":"..","hook_type":"..","angle":"..","audience":"..","confidence":0.0-1.0}
        PROMPT;

    /**
     * @throws \App\Services\Ai\Exceptions\AiException
     */
    public function tag(Ad $ad, string $imageBase64, string $mediaType): void
    {
        $driver = Ai::driver();
        $response = $driver->visionJson(
            self::SYSTEM,
            "Classify this ad creative. The ad is named: \"{$ad->name}\".",
            $imageBase64,
            $mediaType,
        );

        $str = fn ($v) => is_string($v) && trim($v) !== '' ? Str::of($v)->lower()->trim()->limit(40, '')->value() : null;
        $confidence = isset($response['confidence']) && is_numeric($response['confidence'])
            ? max(0.0, min(1.0, (float) $response['confidence']))
            : null;

        AdTag::updateOrCreate(
            ['ad_id' => $ad->id],
            [
                'format' => $str($response['format'] ?? null),
                'hook_type' => $str($response['hook_type'] ?? null),
                'angle' => $str($response['angle'] ?? null),
                'audience' => $str($response['audience'] ?? null),
                'inferred_by' => $driver->name().':'.$driver->model().':vision',
                'confidence' => $confidence,
            ],
        );
    }
}
