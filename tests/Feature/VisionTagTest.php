<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\AdTag;
use App\Models\Organization;
use App\Models\User;
use App\Services\Tagging\VisionTagger;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisionTagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('stackx.allowed_emails', ['@stackx.my']);
        Config::set('ai.default', 'anthropic');
        Config::set('ai.providers.anthropic.model', 'claude-sonnet-5');
    }

    private function ad(): Ad
    {
        $org = Organization::firstOrCreate(['slug' => 'stackx'], ['name' => 'STACKx']);
        app(CurrentOrganization::class)->set($org->id);
        $account = AdAccount::create(['organization_id' => $org->id, 'name' => 'Acc', 'currency' => 'MYR']);

        return Ad::create(['organization_id' => $org->id, 'ad_account_id' => $account->id, 'name' => 'Raya Sale']);
    }

    private function fakeVision(): void
    {
        Config::set('ai.providers.anthropic.key', 'test-key');
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode([
                    'format' => 'Image', 'hook_type' => 'Discount',
                    'angle' => 'Seasonal', 'audience' => 'Shoppers', 'confidence' => 0.9,
                ])]],
            ]),
        ]);
    }

    public function test_vision_tagger_upserts_tags_with_vision_provenance(): void
    {
        $ad = $this->ad();
        $this->fakeVision();

        app(VisionTagger::class)->tag($ad, base64_encode('fake-image-bytes'), 'image/png');

        $tag = AdTag::where('ad_id', $ad->id)->firstOrFail();
        $this->assertSame('image', $tag->format);        // normalized
        $this->assertSame('discount', $tag->hook_type);
        $this->assertSame('anthropic:claude-sonnet-5:vision', $tag->inferred_by);
    }

    public function test_route_uploads_tags_and_sets_thumbnail(): void
    {
        Storage::fake('public');
        $ad = $this->ad();
        $this->fakeVision();
        $user = User::factory()->create(['email' => 'buyer@stackx.my']);
        $file = UploadedFile::fake()->create('creative.png', 20, 'image/png');

        $this->actingAs($user)
            ->post("/analytics/ads/{$ad->id}/vision-tag", ['image' => $file])
            ->assertRedirect(route('analytics'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'Vision-tagged'));

        $this->assertDatabaseHas('ad_tags', ['ad_id' => $ad->id]);
        $this->assertNotNull($ad->fresh()->thumbnail_url);
    }

    public function test_route_degrades_without_key(): void
    {
        Storage::fake('public');
        $ad = $this->ad();
        Config::set('ai.providers.anthropic.key', null);
        Http::fake();
        $user = User::factory()->create(['email' => 'buyer@stackx.my']);
        $file = UploadedFile::fake()->create('creative.png', 20, 'image/png');

        $this->actingAs($user)
            ->post("/analytics/ads/{$ad->id}/vision-tag", ['image' => $file])
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'did not run'));

        $this->assertSame(0, AdTag::count());
    }
}
