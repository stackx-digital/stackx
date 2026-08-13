<?php

namespace App\Services\AdLibrary;

use App\Models\Competitor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Wraps the Meta Ad Library API (graph.facebook.com/.../ads_archive). Gated
 * behind FEATURE_META_AD_LIBRARY + a token; when disabled it never touches the
 * network. Handles pagination and rate-limit retries, and normalizes the raw
 * Graph shape into CompetitorAdData.
 */
class MetaAdLibraryClient
{
    public function enabled(): bool
    {
        return (bool) config('ad_library.enabled') && filled(config('ad_library.token'));
    }

    /**
     * Fetch a competitor's ads. Requires a meta_page_id or search_terms.
     *
     * @return array<int, CompetitorAdData>
     *
     * @throws AdLibraryDisabledException
     */
    public function fetch(Competitor $competitor): array
    {
        if (! $this->enabled()) {
            throw new AdLibraryDisabledException(
                'Ad Library sync is disabled — set FEATURE_META_AD_LIBRARY=true and META_AD_LIBRARY_TOKEN.',
            );
        }

        $base = rtrim((string) config('ad_library.base_url'), '/');
        $version = config('ad_library.version');
        $url = "{$base}/{$version}/ads_archive";

        $params = array_filter([
            'access_token' => config('ad_library.token'),
            'ad_reached_countries' => '["'.config('ad_library.country').'"]',
            'ad_active_status' => 'ALL',
            'search_page_ids' => $competitor->meta_page_id ? '["'.$competitor->meta_page_id.'"]' : null,
            'search_terms' => $competitor->meta_page_id ? null : $competitor->search_terms,
            'fields' => 'id,ad_creative_bodies,ad_snapshot_url,ad_delivery_start_time,ad_delivery_stop_time,publisher_platforms',
            'limit' => (int) config('ad_library.page_limit'),
        ]);

        $out = [];
        $pages = 0;
        $maxPages = (int) config('ad_library.max_pages');

        do {
            $response = Http::timeout(30)->retry(3, 1000)->get($url, $params);

            if ($response->failed()) {
                // Stop on error but keep whatever we collected (graceful).
                break;
            }

            foreach ($response->json('data', []) as $record) {
                $out[] = $this->normalize($record);
            }

            $next = $response->json('paging.cursors.after');
            $params['after'] = $next;
            $pages++;
        } while ($next && $pages < $maxPages);

        return $out;
    }

    /** @param array<string, mixed> $record */
    private function normalize(array $record): CompetitorAdData
    {
        $bodies = $record['ad_creative_bodies'] ?? [];

        return new CompetitorAdData(
            adLibraryId: (string) ($record['id'] ?? ''),
            body: is_array($bodies) ? ($bodies[0] ?? null) : null,
            snapshotUrl: $record['ad_snapshot_url'] ?? null,
            mediaUrl: null, // the API returns a snapshot page, not a media file
            cta: null,
            platforms: (array) ($record['publisher_platforms'] ?? []),
            deliveryStart: $this->date($record['ad_delivery_start_time'] ?? null),
            deliveryStop: $this->date($record['ad_delivery_stop_time'] ?? null),
        );
    }

    private function date(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
