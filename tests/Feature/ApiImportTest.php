<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdMetric;
use App\Models\Organization;
use App\Models\User;
use App\Services\Settings\ApiTokenManager;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The inbound push-import path (Settings → API access): an external workflow
 * (e.g. n8n) authenticates with a Bearer token and pushes rows through the
 * same importer the CSV/live-Meta paths use, so isolation and scoring behave
 * identically regardless of how the data arrived.
 */
class ApiImportTest extends TestCase
{
    use RefreshDatabase;

    private function orgWithToken(string $slug): array
    {
        $org = Organization::create(['name' => ucfirst($slug), 'slug' => $slug]);
        app(CurrentOrganization::class)->set($org->id);
        $token = app(ApiTokenManager::class)->generate();
        app(CurrentOrganization::class)->forget();

        return [$org, $token];
    }

    public function test_rejects_requests_without_a_token(): void
    {
        $this->postJson('/api/v1/ads/import', ['rows' => [['ad_name' => 'x', 'spend' => 1]]])
            ->assertStatus(401);
    }

    public function test_rejects_an_invalid_token(): void
    {
        $this->postJson('/api/v1/ads/import', ['rows' => [['ad_name' => 'x']]], [
            'Authorization' => 'Bearer not-a-real-token',
        ])->assertStatus(401);
    }

    public function test_valid_token_imports_rows_scoped_to_its_org(): void
    {
        [$org, $token] = $this->orgWithToken('alpha');

        $response = $this->postJson('/api/v1/ads/import', [
            'account_name' => 'n8n Weekly',
            'rows' => [
                [
                    'ad_name' => 'Raya Sale', 'meta_ad_id' => '111', 'date' => '2026-08-19',
                    'spend' => 100.5, 'impressions' => 5000, 'roas' => 3.2, 'results' => 12,
                ],
            ],
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertOk()->assertJson([
            'account' => 'n8n Weekly',
            'rows_received' => 1,
            'ads_created' => 1,
            'metrics_created' => 1,
        ]);

        app(CurrentOrganization::class)->set($org->id);
        $ad = Ad::where('meta_ad_id', '111')->firstOrFail();
        $this->assertSame('Raya Sale', $ad->name);
        $this->assertSame($org->id, $ad->organization_id);
        $metric = AdMetric::where('ad_id', $ad->id)->firstOrFail();
        $this->assertEquals(100.5, $metric->spend);
        $this->assertEquals(3.2, $metric->roas);
        app(CurrentOrganization::class)->forget();
    }

    public function test_import_is_idempotent(): void
    {
        [, $token] = $this->orgWithToken('beta');
        $payload = [
            'rows' => [[
                'ad_name' => 'Ad A', 'meta_ad_id' => '222', 'date' => '2026-08-19', 'spend' => 50,
            ]],
        ];

        $this->postJson('/api/v1/ads/import', $payload, ['Authorization' => "Bearer {$token}"]);
        $this->postJson('/api/v1/ads/import', $payload, ['Authorization' => "Bearer {$token}"])
            ->assertJson(['ads_created' => 0, 'ads_matched' => 1]);

        $this->assertSame(1, Ad::where('meta_ad_id', '222')->count());
    }

    public function test_a_token_cannot_write_into_another_tenants_data(): void
    {
        [$orgA, $tokenA] = $this->orgWithToken('gamma');
        [$orgB] = $this->orgWithToken('delta');

        $this->postJson('/api/v1/ads/import', [
            'rows' => [['ad_name' => 'Only Gamma', 'date' => '2026-08-19', 'spend' => 10]],
        ], ['Authorization' => "Bearer {$tokenA}"])->assertOk();

        app(CurrentOrganization::class)->set($orgB->id);
        $this->assertSame(0, Ad::where('name', 'Only Gamma')->count());
        app(CurrentOrganization::class)->forget();

        app(CurrentOrganization::class)->set($orgA->id);
        $this->assertSame(1, Ad::where('name', 'Only Gamma')->count());
        app(CurrentOrganization::class)->forget();
    }

    public function test_rows_without_identity_are_rejected(): void
    {
        [, $token] = $this->orgWithToken('epsilon');

        $this->postJson('/api/v1/ads/import', [
            'rows' => [['date' => '2026-08-19', 'spend' => 10]],
        ], ['Authorization' => "Bearer {$token}"])->assertStatus(422);
    }

    public function test_regenerating_invalidates_the_old_token(): void
    {
        [$org, $oldToken] = $this->orgWithToken('zeta');

        app(CurrentOrganization::class)->set($org->id);
        $newToken = app(ApiTokenManager::class)->generate();
        app(CurrentOrganization::class)->forget();

        $this->postJson('/api/v1/ads/import', ['rows' => [['ad_name' => 'x', 'spend' => 1]]], [
            'Authorization' => "Bearer {$oldToken}",
        ])->assertStatus(401);

        $this->postJson('/api/v1/ads/import', ['rows' => [['ad_name' => 'x', 'spend' => 1]]], [
            'Authorization' => "Bearer {$newToken}",
        ])->assertOk();
    }

    public function test_settings_page_can_generate_and_revoke_a_token(): void
    {
        $org = Organization::create(['name' => 'Theta', 'slug' => 'theta']);
        $user = User::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user)
            ->post('/settings/api-token')
            ->assertRedirect()
            ->assertSessionHas('apiToken');

        app(CurrentOrganization::class)->set($org->id);
        $this->assertTrue(app(ApiTokenManager::class)->hasToken());
        app(CurrentOrganization::class)->forget();

        $this->actingAs($user)->delete('/settings/api-token')->assertRedirect();

        app(CurrentOrganization::class)->set($org->id);
        $this->assertFalse(app(ApiTokenManager::class)->hasToken());
        app(CurrentOrganization::class)->forget();
    }
}
