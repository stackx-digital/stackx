<?php

namespace Tests\Feature;

use App\Models\CreativeBrief;
use App\Models\Organization;
use App\Models\User;
use App\Services\Creation\BriefGenerator;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BriefTest extends TestCase
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

    private function fakeBrief(): void
    {
        Config::set('ai.providers.anthropic.key', 'test-key');
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode([
                    'objective' => 'Drive Raya sales',
                    'target_audience' => 'MY families',
                    'big_idea' => 'Balik kampung ready',
                    'angle' => 'seasonal',
                    'hooks' => ['Jimat besar!', 'Last minute Raya', ''],
                    'visual_direction' => 'Warm tones',
                    'copy_points' => ['Free postage', 'Premium cotton'],
                    'cta' => 'Shop Now',
                ])]],
            ]),
        ]);
    }

    public function test_generates_and_persists_structured_brief(): void
    {
        $this->org();
        $this->fakeBrief();

        $brief = app(BriefGenerator::class)->generate([
            'product' => 'Baju Raya RM89',
            'created_by' => User::factory()->create(['email' => 'b@stackx.my'])->id,
        ]);

        $this->assertSame('Drive Raya sales', $brief->output['objective']);
        $this->assertSame(['Jimat besar!', 'Last minute Raya'], $brief->output['hooks']); // blank filtered
        $this->assertSame('anthropic:claude-sonnet-5', $brief->generated_by);
        $this->assertDatabaseCount('creative_briefs', 1);
    }

    public function test_brief_route_and_degrade(): void
    {
        $this->org();
        $user = User::factory()->create(['email' => 'buyer@stackx.my']);

        // No key → graceful degrade.
        Config::set('ai.providers.anthropic.key', null);
        Http::fake();
        $this->actingAs($user)->post('/create/brief', ['product' => 'X'])
            ->assertRedirect(route('create'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'did not run'));
        $this->assertSame(0, CreativeBrief::count());
    }
}
