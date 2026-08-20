<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\AdMetric;
use App\Models\Organization;
use App\Models\User;
use App\Services\Marketing\MetaException;
use App\Services\Marketing\MetaSync;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $org = Organization::create(['name' => 'Acme', 'slug' => 'acme']);
        app(CurrentOrganization::class)->set($org->id);
        $this->org = $org;
    }

    private Organization $org;

    private function connect(): void
    {
        Config::set('meta.token', 'test-token');
        Config::set('meta.ad_account_id', '123456789');
    }

    private function fakeInsights(array $data): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['data' => $data, 'paging' => (object) []]),
        ]);
    }

    public function test_sync_writes_ads_and_daily_metrics_from_meta(): void
    {
        $this->connect();
        $this->fakeInsights([[
            'ad_id' => '111', 'ad_name' => 'Raya Sale', 'date_start' => '2026-08-01',
            'spend' => '150.5', 'impressions' => '10000', 'reach' => '8000',
            'ctr' => '1.2', 'inline_link_click_ctr' => '0.9', 'cpc' => '0.5', 'cpm' => '15',
            'purchase_roas' => [['action_type' => 'omni_purchase', 'value' => '3.5']],
            'actions' => [['action_type' => 'omni_purchase', 'value' => '20']],
            'cost_per_action_type' => [['action_type' => 'omni_purchase', 'value' => '7.53']],
            'video_thruplay_watched_actions' => [['action_type' => 'video_view', 'value' => '500']],
        ]]);

        $result = app(MetaSync::class)->sync();

        $this->assertSame(1, $result->adsCreated);

        $ad = Ad::where('meta_ad_id', '111')->firstOrFail();
        $this->assertSame('Raya Sale', $ad->name);

        $metric = AdMetric::where('ad_id', $ad->id)->firstOrFail();
        $this->assertEquals(150.5, $metric->spend);
        $this->assertEquals(10000, $metric->impressions);
        $this->assertEquals(3.5, $metric->roas);
        $this->assertEquals(20, $metric->results);
        $this->assertEquals(7.53, $metric->cost_per_result);
        $this->assertEquals(500, $metric->thruplays);
        $this->assertEquals(0.9, $metric->ctr_link);
    }

    public function test_sync_handles_multiple_ad_accounts(): void
    {
        Config::set('meta.token', 'test-token');
        Config::set('meta.ad_account_id', 'act_111, act_222');

        Http::fake([
            'graph.facebook.com/*act_111/insights*' => Http::response(['data' => [[
                'ad_id' => 'a1', 'ad_name' => 'Client A ad', 'date_start' => '2026-08-01', 'spend' => '10',
            ]]]),
            'graph.facebook.com/*act_222/insights*' => Http::response(['data' => [[
                'ad_id' => 'b1', 'ad_name' => 'Client B ad', 'date_start' => '2026-08-01', 'spend' => '20',
            ]]]),
        ]);

        $result = app(MetaSync::class)->sync();

        $this->assertSame(2, $result->adsCreated);
        $this->assertSame(2, AdAccount::whereNotNull('meta_ad_account_id')->count());
        $this->assertTrue(Ad::where('meta_ad_id', 'a1')->exists());
        $this->assertTrue(Ad::where('meta_ad_id', 'b1')->exists());
    }

    public function test_sync_is_idempotent(): void
    {
        $this->connect();
        $this->fakeInsights([[
            'ad_id' => '111', 'ad_name' => 'Raya Sale', 'date_start' => '2026-08-01',
            'spend' => '100', 'impressions' => '5000',
        ]]);

        app(MetaSync::class)->sync();
        app(MetaSync::class)->sync();

        $this->assertSame(1, Ad::where('meta_ad_id', '111')->count());
        $this->assertSame(1, AdMetric::count());
    }

    public function test_account_id_is_normalized_with_act_prefix(): void
    {
        $this->connect();
        $this->fakeInsights([]);

        app(MetaSync::class)->sync();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/act_123456789/insights'));
    }

    public function test_sync_throws_when_not_connected(): void
    {
        Config::set('meta.token', null);
        Config::set('meta.ad_account_id', null);

        $this->expectException(MetaException::class);
        app(MetaSync::class)->sync();
    }

    public function test_route_syncs_and_redirects_to_analytics(): void
    {
        $this->connect();
        $this->fakeInsights([[
            'ad_id' => '111', 'ad_name' => 'Ad', 'date_start' => '2026-08-01', 'spend' => '10',
        ]]);
        $user = User::factory()->create(['organization_id' => $this->org->id]);

        $this->actingAs($user)
            ->post('/analytics/meta/sync')
            ->assertRedirect(route('analytics'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'Synced from Meta'));
    }

    public function test_route_redirects_to_settings_when_not_connected(): void
    {
        // No creds and no per-tenant settings → tenant middleware leaves meta unset.
        Config::set('meta.token', null);
        Config::set('meta.ad_account_id', null);
        $user = User::factory()->create(['organization_id' => $this->org->id]);

        $this->actingAs($user)
            ->post('/analytics/meta/sync')
            ->assertRedirect(route('settings'));
    }
}
