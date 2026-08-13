<?php

namespace App\Services\Tagging;

use App\Models\Ad;

/**
 * The input to the tagging service — deliberately pluggable (§3). MVP tags from
 * the ad name only; Phase 2 adds the creative thumbnail for Claude/GPT vision
 * without changing the tagger's interface.
 */
class TaggableAd
{
    public function __construct(
        public readonly int $adId,
        public readonly string $name,
        public readonly ?string $thumbnailUrl = null,
    ) {}

    public static function fromAd(Ad $ad): self
    {
        return new self($ad->id, $ad->name, $ad->thumbnail_url);
    }
}
