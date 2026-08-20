<?php

namespace App\Services\AdLibrary;

use App\Models\Competitor;
use App\Models\CompetitorAd;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Syncs a competitor's ads from the Ad Library into competitor_ads (§5, P2).
 * Idempotent: deduped by (competitor, ad_library_id); each run refreshes
 * last_seen / is_active / days_running and marks ads no longer returned as
 * inactive. Never fabricates — days_running comes from the delivery window.
 */
class CompetitorSync
{
    public function __construct(private readonly MetaAdLibraryClient $client) {}

    /**
     * @throws AdLibraryDisabledException
     */
    public function sync(Competitor $competitor): SyncResult
    {
        $data = $this->client->fetch($competitor);
        $result = new SyncResult;
        $result->fetched = count($data);
        $today = Carbon::today();

        DB::transaction(function () use ($competitor, $data, $today, $result) {
            $seen = [];

            foreach ($data as $d) {
                if ($d->adLibraryId === '') {
                    continue;
                }
                $seen[] = $d->adLibraryId;

                $existing = CompetitorAd::where('competitor_id', $competitor->id)
                    ->where('ad_library_id', $d->adLibraryId)
                    ->first();

                $firstSeen = $d->deliveryStart?->toDateString() ?? $today->toDateString();
                if ($existing?->first_seen) {
                    $firstSeen = min($existing->first_seen->toDateString(), $firstSeen);
                }

                $ad = CompetitorAd::updateOrCreate(
                    ['competitor_id' => $competitor->id, 'ad_library_id' => $d->adLibraryId],
                    [
                        'body' => $d->body,
                        'snapshot_url' => $d->snapshotUrl,
                        'media_url' => $d->mediaUrl,
                        'cta' => $d->cta,
                        'platforms' => $d->platforms,
                        'first_seen' => $firstSeen,
                        'last_seen' => $today->toDateString(),
                        'days_running' => $d->daysRunning($today),
                        'is_active' => $d->isActive(),
                    ],
                );

                $ad->wasRecentlyCreated ? $result->created++ : $result->updated++;
            }

            // Ads no longer returned are treated as stopped — but only when this
            // run actually returned data (don't nuke everything on an empty pull).
            if (! empty($seen)) {
                $result->deactivated = CompetitorAd::where('competitor_id', $competitor->id)
                    ->whereNotIn('ad_library_id', $seen)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $competitor->update(['last_synced_at' => $today]);
        });

        return $result;
    }
}
