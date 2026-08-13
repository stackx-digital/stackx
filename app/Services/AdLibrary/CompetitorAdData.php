<?php

namespace App\Services\AdLibrary;

use Illuminate\Support\Carbon;

/**
 * A normalized competitor ad from the Meta Ad Library, decoupled from the raw
 * Graph API shape. days_running is computed from the delivery window.
 */
class CompetitorAdData
{
    /** @param array<int, string> $platforms */
    public function __construct(
        public readonly string $adLibraryId,
        public readonly ?string $body,
        public readonly ?string $snapshotUrl,
        public readonly ?string $mediaUrl,
        public readonly ?string $cta,
        public readonly array $platforms,
        public readonly ?Carbon $deliveryStart,
        public readonly ?Carbon $deliveryStop,
    ) {}

    public function isActive(): bool
    {
        return $this->deliveryStop === null;
    }

    /** Days the ad has run: from start to stop (or today if still active). */
    public function daysRunning(?Carbon $today = null): int
    {
        if ($this->deliveryStart === null) {
            return 0;
        }

        $end = $this->deliveryStop ?? ($today ?? Carbon::today());

        return max(0, $this->deliveryStart->diffInDays($end));
    }
}
