<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\AdScore;
use App\Models\Organization;
use App\Services\Scoring\ScoringService;
use App\Support\CurrentOrganization;
use Database\Seeders\DemoAdsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(): AdAccount
    {
        $org = Organization::firstOrCreate(['slug' => 'stackx'], ['name' => 'STACKx']);
        app(CurrentOrganization::class)->set($org->id);
        (new DemoAdsSeeder)->run();

        return AdAccount::where('name', 'Demo — Raya Campaign')->firstOrFail();
    }

    public function test_scores_all_demo_ads(): void
    {
        $account = $this->seedDemo();

        $count = app(ScoringService::class)->scoreAccount($account);

        $this->assertSame(7, $count);
        $this->assertSame(7, AdScore::count());
    }

    public function test_scores_stay_within_range_and_spread(): void
    {
        $account = $this->seedDemo();
        app(ScoringService::class)->scoreAccount($account);

        $hooks = AdScore::whereNotNull('hook')->pluck('hook');
        $this->assertGreaterThan(1, $hooks->unique()->count(), 'scores should spread');

        AdScore::all()->each(function (AdScore $s) {
            foreach (['hook', 'watch', 'click', 'convert'] as $stage) {
                if ($s->{$stage} !== null) {
                    $this->assertGreaterThanOrEqual(0, $s->{$stage});
                    $this->assertLessThanOrEqual(100, $s->{$stage});
                }
            }
        });
    }

    public function test_produces_both_winners_and_losers(): void
    {
        $account = $this->seedDemo();
        app(ScoringService::class)->scoreAccount($account);

        $actions = AdScore::pluck('action');
        $this->assertTrue($actions->contains('scale'), 'expected at least one scale');
        $this->assertTrue($actions->contains('cut'), 'expected at least one cut');
    }

    public function test_image_ads_get_na_hook_not_a_fabricated_zero(): void
    {
        $account = $this->seedDemo();
        app(ScoringService::class)->scoreAccount($account);

        // "Free Shipping MY | Image | Offer" has no video plays → Hook is N/A.
        $imageScore = AdScore::whereHas('ad', fn ($q) => $q->where('name', 'like', 'Free Shipping%'))->first();

        $this->assertNotNull($imageScore);
        $this->assertNull($imageScore->hook);
    }
}
