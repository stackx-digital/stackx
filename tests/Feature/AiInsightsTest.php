<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\AdScore;
use App\Models\AdTag;
use App\Models\Organization;
use App\Models\User;
use App\Services\Scoring\ScoringService;
use App\Support\CurrentOrganization;
use Database\Seeders\DemoAdsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('stackx.allowed_emails', ['@stackx.my']);
        Config::set('ai.default', 'anthropic');
        Config::set('ai.providers.anthropic.model', 'claude-sonnet-5');
    }

    private function seedScoredDemo(): void
    {
        $org = Organization::firstOrCreate(['slug' => 'stackx'], ['name' => 'STACKx']);
        app(CurrentOrganization::class)->set($org->id);
        (new DemoAdsSeeder)->run();
        app(ScoringService::class)->scoreAccount(
            AdAccount::where('name', 'Demo — Raya Campaign')->firstOrFail(),
        );
    }

    private function user(): User
    {
        return User::factory()->create(['email' => 'buyer@stackx.my']);
    }

    private function anthropic(array $payload): array
    {
        return ['content' => [['type' => 'text', 'text' => json_encode($payload)]]];
    }

    public function test_generate_ai_insights_tags_and_recommends(): void
    {
        Config::set('ai.providers.anthropic.key', 'test-key');
        $this->seedScoredDemo();

        $tags = collect(range(0, 7))->map(fn ($i) => [
            'i' => $i, 'format' => 'video', 'hook_type' => 'discount',
            'angle' => 'seasonal', 'audience' => 'general', 'confidence' => 0.7,
        ])->all();

        $recs = collect(range(0, 7))->map(fn ($i) => [
            'i' => $i, 'text' => 'Strong hook and click percentiles justify scaling.',
        ])->all();

        Http::fakeSequence('api.anthropic.com/*')
            ->push($this->anthropic(['tags' => $tags]))
            ->push($this->anthropic(['recommendations' => $recs]));

        $this->actingAs($this->user())
            ->post('/analytics/ai')
            ->assertRedirect(route('analytics'))
            ->assertSessionHas('status');

        $this->assertGreaterThan(0, AdTag::count());
        $this->assertGreaterThan(0, AdScore::whereNotNull('ai_recommendation')->count());
    }

    public function test_missing_key_degrades_gracefully(): void
    {
        Config::set('ai.providers.anthropic.key', null);
        $this->seedScoredDemo();
        Http::fake();

        $this->actingAs($this->user())
            ->post('/analytics/ai')
            ->assertRedirect(route('analytics'))
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'did not run'));

        $this->assertSame(0, AdTag::count());
        Http::assertNothingSent();
    }
}
