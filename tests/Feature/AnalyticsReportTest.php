<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\Organization;
use App\Models\User;
use App\Services\Scoring\ScoringService;
use App\Support\CurrentOrganization;
use Database\Seeders\DemoAdsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnalyticsReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('stackx.allowed_emails', ['@stackx.my']);
    }

    private function seedScoredDemo(): void
    {
        $org = Organization::firstOrCreate(['slug' => 'stackx'], ['name' => 'STACKx']);
        app(CurrentOrganization::class)->set($org->id);
        (new DemoAdsSeeder)->run();
        $account = AdAccount::where('name', 'Demo — Raya Campaign')->firstOrFail();
        app(ScoringService::class)->scoreAccount($account);
    }

    private function user(): User
    {
        return User::factory()->create(['email' => 'buyer@stackx.my']);
    }

    public function test_index_returns_scored_ads_and_summary(): void
    {
        $this->seedScoredDemo();

        $this->actingAs($this->user())
            ->get('/analytics')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Analytics/Index')
                ->has('ads', 7)
                ->has('ads.0', fn (Assert $ad) => $ad
                    ->hasAll(['id', 'name', 'account', 'spend', 'roas', 'scores', 'action', 'actionReason'])
                    ->etc())
                ->where('summary.adCount', 7)
                ->has('summary.blendedRoas')
                ->has('summary.blendedCpa'));
    }

    public function test_ad_detail_endpoint_returns_metrics_and_daily_trend(): void
    {
        $this->seedScoredDemo();
        $ad = Ad::where('name', 'like', 'Raya Sale%')->firstOrFail();

        $response = $this->actingAs($this->user())->getJson("/analytics/ads/{$ad->id}");

        $response->assertOk()
            ->assertJsonPath('ad.id', $ad->id)
            ->assertJsonPath('ad.name', $ad->name)
            ->assertJsonStructure([
                'ad' => ['id', 'name', 'account', 'status'],
                'aggregate' => ['spend', 'impressions', 'roas', 'cpr', 'ctrLink', 'results'],
                'scores' => ['hook', 'watch', 'click', 'convert'],
                'action',
                'daily' => [['date', 'spend', 'roas']],
            ]);

        $this->assertCount(3, $response->json('daily'));
    }

    public function test_ad_detail_is_org_scoped(): void
    {
        $this->seedScoredDemo();

        // Create an ad in a different org.
        $other = Organization::create(['name' => 'Other', 'slug' => 'other']);
        app(CurrentOrganization::class)->set($other->id);
        $otherAccount = AdAccount::create(['organization_id' => $other->id, 'name' => 'Other Acc', 'currency' => 'MYR']);
        $otherAd = Ad::create(['organization_id' => $other->id, 'ad_account_id' => $otherAccount->id, 'name' => 'Secret Ad']);

        // Back to the default org for the request.
        app(CurrentOrganization::class)->forget();

        $this->actingAs($this->user())
            ->getJson("/analytics/ads/{$otherAd->id}")
            ->assertNotFound();
    }
}
