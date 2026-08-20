<?php

namespace Tests\Feature;

use App\Models\Competitor;
use App\Models\CompetitorAd;
use App\Models\Organization;
use App\Services\AdLibrary\AdLibraryDisabledException;
use App\Services\AdLibrary\CompetitorSync;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompetitorSyncTest extends TestCase
{
    use RefreshDatabase;

    private function competitor(): Competitor
    {
        $org = Organization::create(['name' => 'STACKx', 'slug' => 'stackx']);
        app(CurrentOrganization::class)->set($org->id);

        return Competitor::create([
            'organization_id' => $org->id,
            'name' => 'Rival',
            'meta_page_id' => '123',
        ]);
    }

    private function enable(): void
    {
        Config::set('ad_library.enabled', true);
        Config::set('ad_library.token', 'test-token');
    }

    private function fakeAds(array $data): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['data' => $data, 'paging' => []]),
        ]);
    }

    private function ad(string $id, string $start, ?string $stop = null): array
    {
        return [
            'id' => $id,
            'ad_creative_bodies' => ["Body for {$id}"],
            'ad_snapshot_url' => "https://facebook.com/ads/library/?id={$id}",
            'ad_delivery_start_time' => $start,
            'ad_delivery_stop_time' => $stop,
            'publisher_platforms' => ['facebook', 'instagram'],
        ];
    }

    public function test_disabled_sync_throws(): void
    {
        Config::set('ad_library.enabled', false);
        $competitor = $this->competitor();

        $this->expectException(AdLibraryDisabledException::class);
        app(CompetitorSync::class)->sync($competitor);
    }

    public function test_sync_upserts_and_computes_days_running(): void
    {
        $this->enable();
        $competitor = $this->competitor();
        $start = Carbon::today()->subDays(30)->toDateString();
        $this->fakeAds([$this->ad('A', $start), $this->ad('B', Carbon::today()->subDays(5)->toDateString(), Carbon::today()->subDays(1)->toDateString())]);

        $result = app(CompetitorSync::class)->sync($competitor);

        $this->assertSame(2, $result->created);
        $a = CompetitorAd::where('ad_library_id', 'A')->first();
        $this->assertSame(30, $a->days_running);
        $this->assertTrue($a->is_active);

        $b = CompetitorAd::where('ad_library_id', 'B')->first();
        $this->assertSame(4, $b->days_running);   // 5d ago → 1d ago
        $this->assertFalse($b->is_active);        // has a stop time
    }

    public function test_resync_is_idempotent_and_deactivates_missing(): void
    {
        $this->enable();
        $competitor = $this->competitor();
        $start = Carbon::today()->subDays(30)->toDateString();

        Http::fakeSequence('graph.facebook.com/*')
            ->push(['data' => [$this->ad('A', $start), $this->ad('B', $start)], 'paging' => []])
            ->push(['data' => [$this->ad('A', $start)], 'paging' => []]);

        app(CompetitorSync::class)->sync($competitor);

        // Second pull no longer returns B → B is marked stopped, no dupes.
        $result = app(CompetitorSync::class)->sync($competitor);

        $this->assertSame(2, CompetitorAd::count());  // no duplicates
        $this->assertSame(1, $result->updated);
        $this->assertSame(1, $result->deactivated);
        $this->assertFalse(CompetitorAd::where('ad_library_id', 'B')->first()->is_active);
    }
}
