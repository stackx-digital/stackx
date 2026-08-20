<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\AdEmbedding;
use App\Models\Competitor;
use App\Models\CompetitorAd;
use App\Models\Organization;
use App\Models\User;
use App\Services\Embedding\EmbeddingService;
use App\Services\Embedding\SemanticSearch;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DiscoveryTest extends TestCase
{
    use RefreshDatabase;

    /** Deterministic bag-of-words vector so search ranking is real + offline. */
    private const VOCAB = ['raya', 'discount', 'sale', 'shipping', 'free', 'video'];

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('stackx.allowed_emails', ['@stackx.my']);
        Config::set('embedding.default', 'openai');
        Config::set('embedding.providers.openai.key', 'test-key');
    }

    private function vec(string $text): array
    {
        $t = Str::lower($text);

        return array_map(fn ($w) => (float) substr_count($t, $w), self::VOCAB);
    }

    private function fakeEmbeddings(): void
    {
        Http::fake([
            'api.openai.com/*' => function (Request $request) {
                $data = [];
                foreach ($request['input'] as $i => $text) {
                    $data[] = ['index' => $i, 'embedding' => $this->vec($text)];
                }

                return Http::response(['data' => $data]);
            },
        ]);
    }

    private function seedCorpus(): void
    {
        $org = Organization::firstOrCreate(['slug' => 'stackx'], ['name' => 'STACKx']);
        app(CurrentOrganization::class)->set($org->id);
        $account = AdAccount::create(['organization_id' => $org->id, 'name' => 'Acc', 'currency' => 'MYR']);

        Ad::create(['organization_id' => $org->id, 'ad_account_id' => $account->id, 'name' => 'Raya Sale Discount Video']);
        Ad::create(['organization_id' => $org->id, 'ad_account_id' => $account->id, 'name' => 'Free Shipping Offer']);

        $competitor = Competitor::create(['organization_id' => $org->id, 'name' => 'Rival']);
        CompetitorAd::create(['competitor_id' => $competitor->id, 'ad_library_id' => 'x1', 'body' => 'Mega discount sale this Raya', 'days_running' => 10]);
    }

    public function test_embeds_ads_and_competitor_ads_then_skips_unchanged(): void
    {
        $this->fakeEmbeddings();
        $this->seedCorpus();

        $first = app(EmbeddingService::class)->embedCorpus();
        $this->assertSame(3, $first->embedded);          // 2 ads + 1 competitor ad
        $this->assertSame(3, AdEmbedding::count());

        $second = app(EmbeddingService::class)->embedCorpus();
        $this->assertSame(0, $second->embedded);
        $this->assertSame(3, $second->skipped);
    }

    public function test_semantic_search_ranks_by_meaning(): void
    {
        $this->fakeEmbeddings();
        $this->seedCorpus();
        app(EmbeddingService::class)->embedCorpus();

        $results = app(SemanticSearch::class)->search('raya discount sale', 5);

        $this->assertNotEmpty($results);
        // The Raya discount ad should outrank the free-shipping ad.
        $this->assertStringContainsStringIgnoringCase('raya', $results[0]['title'].$results[0]['snippet']);
        $titles = collect($results)->pluck('title')->implode('|');
        $this->assertStringNotContainsString('Free Shipping', $results[0]['title']);
        $this->assertStringContainsString('Free Shipping', $titles); // still indexed, just lower
    }

    public function test_discovery_page_returns_results(): void
    {
        $this->fakeEmbeddings();
        $this->seedCorpus();
        app(EmbeddingService::class)->embedCorpus();
        $user = User::factory()->create(['email' => 'buyer@stackx.my']);

        $this->actingAs($user)
            ->get('/discovery?q='.urlencode('raya discount'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Discovery/Index')
                ->where('embeddedCount', 3)
                ->has('results')
                ->where('results.0.source', fn ($s) => in_array($s, ['ad', 'competitor_ad'], true)));
    }

    public function test_embed_degrades_without_key(): void
    {
        Config::set('embedding.providers.openai.key', null);
        $this->seedCorpus();
        Http::fake();
        $user = User::factory()->create(['email' => 'buyer@stackx.my']);

        $this->actingAs($user)
            ->post('/discovery/embed')
            ->assertRedirect(route('discovery'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'did not run'));

        $this->assertSame(0, AdEmbedding::count());
        Http::assertNothingSent();
    }
}
