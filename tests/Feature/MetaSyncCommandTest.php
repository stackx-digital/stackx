<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The scheduled command must overlay the SAME credentials (token, account,
 * app id, app secret) that the web "Sync now" path uses via TenantSettings —
 * a prior bug only overlaid token + account, silently dropping the app secret
 * and breaking appsecret_proof on every scheduled run.
 */
class MetaSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    private function orgWithSettings(string $slug, array $settings): Organization
    {
        $org = Organization::create(['name' => ucfirst($slug), 'slug' => $slug]);
        app(CurrentOrganization::class)->set($org->id);
        OrganizationSetting::create(array_merge(['organization_id' => $org->id], $settings));
        app(CurrentOrganization::class)->forget();

        return $org;
    }

    public function test_command_sends_appsecret_proof_from_the_orgs_own_app_secret(): void
    {
        $this->orgWithSettings('alpha', [
            'meta_system_token' => 'org-token',
            'meta_ad_account_id' => 'act_111',
            'meta_app_id' => 'app-1',
            'meta_app_secret' => 'org-secret',
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['data' => []])]);

        $this->artisan('meta:sync')->assertExitCode(0);

        $expectedProof = hash_hmac('sha256', 'org-token', 'org-secret');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'appsecret_proof='.$expectedProof));
    }

    public function test_credentials_do_not_leak_between_orgs(): void
    {
        $this->orgWithSettings('alpha', [
            'meta_system_token' => 'alpha-token',
            'meta_ad_account_id' => 'act_111',
            'meta_app_secret' => 'alpha-secret',
        ]);
        $this->orgWithSettings('beta', [
            'meta_system_token' => 'beta-token',
            'meta_ad_account_id' => 'act_222',
            // No app secret for beta — its requests must carry no proof at all,
            // never alpha's.
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['data' => []])]);

        $this->artisan('meta:sync')->assertExitCode(0);

        $alphaProof = hash_hmac('sha256', 'alpha-token', 'alpha-secret');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'act_111')
            && str_contains($request->url(), 'access_token=alpha-token')
            && str_contains($request->url(), 'appsecret_proof='.$alphaProof));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'act_222')
            && str_contains($request->url(), 'access_token=beta-token')
            && ! str_contains($request->url(), 'appsecret_proof'));
    }

    public function test_command_skips_orgs_without_meta_connected(): void
    {
        Organization::create(['name' => 'NoMeta', 'slug' => 'no-meta']);

        Http::fake();

        $this->artisan('meta:sync')->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_command_writes_ads_for_a_connected_org(): void
    {
        $org = $this->orgWithSettings('gamma', [
            'meta_system_token' => 'gamma-token',
            'meta_ad_account_id' => 'act_333',
        ]);

        Http::fake([
            'graph.facebook.com/*/insights*' => Http::response(['data' => [[
                'ad_id' => '999', 'ad_name' => 'Gamma ad', 'date_start' => '2026-08-01', 'spend' => '10',
            ]]]),
            'graph.facebook.com/*/ads*' => Http::response(['data' => []]),
        ]);

        $this->artisan('meta:sync')->assertExitCode(0);

        app(CurrentOrganization::class)->set($org->id);
        $this->assertTrue(Ad::where('meta_ad_id', '999')->exists());
        app(CurrentOrganization::class)->forget();
    }
}
