<?php

namespace App\Http\Controllers;

use App\Models\Competitor;
use App\Models\CompetitorAd;
use App\Services\AdLibrary\MetaAdLibraryClient;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P2 Brand Spy report. Lists tracked competitors and their ads, ordered by
 * days_running (a computed proxy for a winning ad). Live sync is behind a flag;
 * when off, this renders saved/demo data and the UI says so.
 */
class SpyController extends Controller
{
    public function index(MetaAdLibraryClient $client): Response
    {
        $competitors = Competitor::with(['ads' => fn ($q) => $q->orderByDesc('days_running')])
            ->orderBy('name')
            ->get()
            ->map(fn (Competitor $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'metaPageId' => $c->meta_page_id,
                'lastSyncedAt' => $c->last_synced_at?->toDateString(),
                'adCount' => $c->ads->count(),
                'activeCount' => $c->ads->where('is_active', true)->count(),
                'ads' => $c->ads->map(fn (CompetitorAd $ad) => [
                    'id' => $ad->id,
                    'body' => $ad->body,
                    'snapshotUrl' => $ad->snapshot_url,
                    'mediaUrl' => $ad->media_url,
                    'platforms' => $ad->platforms,
                    'daysRunning' => $ad->days_running,
                    'isActive' => $ad->is_active,
                    'firstSeen' => $ad->first_seen?->toDateString(),
                    'lastSeen' => $ad->last_seen?->toDateString(),
                ])->values(),
            ]);

        return Inertia::render('Spy/Index', [
            'competitors' => $competitors,
            'adLibraryEnabled' => $client->enabled(),
        ]);
    }
}
