<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\AdTag;
use App\Models\Organization;
use App\Services\Tagging\AdTagger;
use App\Services\Tagging\TaggableAd;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdTaggerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ai.default', 'anthropic');
        Config::set('ai.providers.anthropic.key', 'test-key');
        Config::set('ai.providers.anthropic.model', 'claude-sonnet-5');
    }

    /** @return array<int, TaggableAd> */
    private function twoAds(): array
    {
        $org = Organization::create(['name' => 'STACKx', 'slug' => 'stackx']);
        app(CurrentOrganization::class)->set($org->id);
        $account = AdAccount::create(['organization_id' => $org->id, 'name' => 'Acc', 'currency' => 'MYR']);

        return [
            TaggableAd::fromAd(Ad::create(['organization_id' => $org->id, 'ad_account_id' => $account->id, 'name' => 'Raya Sale | Video | Discount'])),
            TaggableAd::fromAd(Ad::create(['organization_id' => $org->id, 'ad_account_id' => $account->id, 'name' => 'Free Shipping | Image | Offer'])),
        ];
    }

    private function fakeAnthropic(array $tags): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode(['tags' => $tags])]],
            ]),
        ]);
    }

    public function test_tags_are_inferred_and_upserted_with_provenance(): void
    {
        $ads = $this->twoAds();
        $this->fakeAnthropic([
            ['i' => 0, 'format' => 'Video', 'hook_type' => 'Discount', 'angle' => 'Seasonal', 'audience' => 'General', 'confidence' => 0.8],
            ['i' => 1, 'format' => 'Image', 'hook_type' => 'Offer', 'angle' => 'Savings', 'audience' => 'Shoppers', 'confidence' => 1.5],
        ]);

        $result = (new AdTagger)->tag($ads);

        $this->assertSame(2, $result->tagged);
        $this->assertSame(0, $result->failedBatches);

        $tag = AdTag::where('ad_id', $ads[0]->adId)->first();
        $this->assertSame('video', $tag->format);          // normalized lowercase
        $this->assertSame('discount', $tag->hook_type);
        $this->assertSame('anthropic:claude-sonnet-5', $tag->inferred_by);

        // Confidence clamped to [0,1].
        $this->assertEquals(1.0, (float) AdTag::where('ad_id', $ads[1]->adId)->first()->confidence);
    }

    public function test_invalid_items_are_skipped(): void
    {
        $ads = $this->twoAds();
        $this->fakeAnthropic([
            ['i' => 0, 'format' => 'Video', 'hook_type' => 'Discount', 'angle' => 'Seasonal', 'audience' => 'General'],
            ['format' => 'Image'], // no index → skipped
        ]);

        $result = (new AdTagger)->tag($ads);

        $this->assertSame(1, $result->tagged);
        $this->assertSame(0, AdTag::where('ad_id', $ads[1]->adId)->count());
    }

    public function test_ai_failure_is_non_fatal(): void
    {
        $ads = $this->twoAds();
        Http::fake(['api.anthropic.com/*' => Http::response('upstream error', 500)]);

        $result = (new AdTagger)->tag($ads);

        $this->assertSame(0, $result->tagged);
        $this->assertGreaterThan(0, $result->failedBatches);
        $this->assertSame(0, AdTag::count()); // analytics untouched
    }

    public function test_missing_key_degrades_without_network(): void
    {
        $ads = $this->twoAds();
        Config::set('ai.providers.anthropic.key', null);
        Http::fake(); // any accidental call would be recorded

        $result = (new AdTagger)->tag($ads);

        $this->assertSame(0, $result->tagged);
        $this->assertGreaterThan(0, $result->failedBatches);
        Http::assertNothingSent();
    }
}
