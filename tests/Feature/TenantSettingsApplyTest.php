<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Services\Settings\TenantSettings;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The overlay is the mechanism that makes every existing service (AiManager,
 * EmbeddingManager, MetaAdLibraryClient) use a tenant's own credentials
 * without any change to those services.
 */
class TenantSettingsApplyTest extends TestCase
{
    use RefreshDatabase;

    private function org(string $slug): Organization
    {
        return Organization::create(['name' => ucfirst($slug), 'slug' => $slug]);
    }

    public function test_apply_overlays_current_tenant_credentials_onto_config(): void
    {
        Config::set('ai.providers.anthropic.key', 'env-fallback');
        Config::set('ad_library.enabled', false);
        Config::set('ad_library.token', null);

        $org = $this->org('alpha');
        app(CurrentOrganization::class)->set($org->id);
        OrganizationSetting::create([
            'organization_id' => $org->id,
            'ai_provider' => 'openai',
            'anthropic_api_key' => 'tenant-anthropic',
            'openai_api_key' => 'tenant-openai',
            'meta_ad_library_token' => 'tenant-meta',
        ]);

        app(TenantSettings::class)->apply();

        $this->assertSame('openai', config('ai.default'));
        $this->assertSame('tenant-anthropic', config('ai.providers.anthropic.key'));
        $this->assertSame('tenant-openai', config('embedding.providers.openai.key'));
        $this->assertSame('tenant-meta', config('ad_library.token'));
        $this->assertTrue(config('ad_library.enabled'));
    }

    public function test_empty_values_fall_back_to_env_defaults(): void
    {
        Config::set('ai.providers.anthropic.key', 'env-fallback');

        $org = $this->org('beta');
        app(CurrentOrganization::class)->set($org->id);
        OrganizationSetting::create([
            'organization_id' => $org->id,
            'ai_provider' => null, // no override
        ]);

        app(TenantSettings::class)->apply();

        // Unset tenant value leaves the deployment default intact.
        $this->assertSame('env-fallback', config('ai.providers.anthropic.key'));
    }

    public function test_apply_is_a_noop_when_the_tenant_has_no_settings_row(): void
    {
        Config::set('ai.providers.anthropic.key', 'env-fallback');

        $org = $this->org('gamma');
        app(CurrentOrganization::class)->set($org->id);

        app(TenantSettings::class)->apply();

        $this->assertSame('env-fallback', config('ai.providers.anthropic.key'));
    }

    public function test_capabilities_reflect_stored_keys(): void
    {
        Config::set('ai.providers.anthropic.key', null);
        Config::set('ai.providers.openai.key', null);
        Config::set('embedding.providers.openai.key', null);
        Config::set('ad_library.token', null);

        $org = $this->org('delta');
        app(CurrentOrganization::class)->set($org->id);
        OrganizationSetting::create([
            'organization_id' => $org->id,
            'ai_provider' => 'anthropic',
            'anthropic_api_key' => 'k',
        ]);

        $caps = app(TenantSettings::class)->capabilities();

        $this->assertTrue($caps['ai']);
        $this->assertFalse($caps['embedding']);
        $this->assertFalse($caps['adLibrary']);
    }
}
