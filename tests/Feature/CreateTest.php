<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\AdScore;
use App\Models\AdVariation;
use App\Models\Organization;
use App\Models\User;
use App\Services\Creation\VariationGenerator;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('stackx.allowed_emails', ['@stackx.my']);
        Config::set('ai.default', 'anthropic');
        Config::set('ai.providers.anthropic.model', 'claude-sonnet-5');
    }

    private function org(): Organization
    {
        $org = Organization::firstOrCreate(['slug' => 'stackx'], ['name' => 'STACKx']);
        app(CurrentOrganization::class)->set($org->id);

        return $org;
    }

    private function user(): User
    {
        return User::factory()->create(['email' => 'buyer@stackx.my']);
    }

    private function fakeVariations(array $variations): void
    {
        Config::set('ai.providers.anthropic.key', 'test-key');
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode(['variations' => $variations])]],
            ]),
        ]);
    }

    public function test_generator_persists_only_valid_variations(): void
    {
        $org = $this->org();
        $this->fakeVariations([
            ['hook' => 'Jimat besar Raya ni!', 'primary_text' => 'Diskaun sampai 60%...', 'headline' => 'Raya Sale', 'angle' => 'savings', 'cta' => 'Shop Now'],
            ['headline' => 'no copy here'], // invalid — dropped
            ['hook' => 'Last call!', 'primary_text' => 'Ends tonight.', 'angle' => 'urgency', 'cta' => 'Grab'],
        ]);

        $variation = app(VariationGenerator::class)->generate([
            'product' => 'Baju Raya RM89',
            'count' => 5,
            'created_by' => $this->user()->id,
        ]);

        $this->assertCount(2, $variation->output);   // invalid one filtered
        $this->assertSame('anthropic:claude-sonnet-5', $variation->generated_by);
        $this->assertSame('Baju Raya RM89', $variation->product);
        $this->assertDatabaseCount('ad_variations', 1);
    }

    public function test_generate_from_winner_ad_via_route(): void
    {
        $org = $this->org();
        $account = AdAccount::create(['organization_id' => $org->id, 'name' => 'Acc', 'currency' => 'MYR']);
        $ad = Ad::create(['organization_id' => $org->id, 'ad_account_id' => $account->id, 'name' => 'Raya Winner']);
        AdScore::create(['ad_id' => $ad->id, 'computed_at' => Carbon::now(), 'action' => 'scale']);

        $this->fakeVariations([
            ['hook' => 'Best seller balik!', 'primary_text' => 'Sekali lagi viral.', 'angle' => 'social-proof', 'cta' => 'Beli'],
        ]);

        $this->actingAs($this->user())
            ->post('/create', ['product' => 'Baju Raya', 'source' => 'ad', 'source_id' => $ad->id, 'count' => 3])
            ->assertRedirect(route('create'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('ad_variations', ['source' => 'ad', 'source_id' => $ad->id]);
    }

    public function test_degrades_without_key(): void
    {
        $this->org();
        Config::set('ai.providers.anthropic.key', null);
        Http::fake();

        $this->actingAs($this->user())
            ->post('/create', ['product' => 'Something', 'count' => 3])
            ->assertRedirect(route('create'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'did not run'));

        $this->assertSame(0, AdVariation::count());
        Http::assertNothingSent();
    }

    public function test_index_renders_sources_and_history(): void
    {
        $this->org();
        $user = $this->user();
        $this->fakeVariations([['hook' => 'x', 'primary_text' => 'y']]);
        app(VariationGenerator::class)->generate(['product' => 'P', 'created_by' => $user->id]);

        $this->actingAs($user)
            ->get('/create')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Create/Index')
                ->has('winners')
                ->has('competitorAds')
                ->has('history', 1));
    }
}
