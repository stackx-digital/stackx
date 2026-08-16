<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $org = Organization::create(['name' => 'Acme', 'slug' => 'acme-'.uniqid()]);

        return User::factory()->create(['organization_id' => $org->id]);
    }

    public function test_settings_page_requires_auth(): void
    {
        $this->get('/settings')->assertRedirect(route('login'));
    }

    public function test_page_renders_without_leaking_secrets(): void
    {
        $user = $this->user();
        app(CurrentOrganization::class)->set($user->organization_id);
        OrganizationSetting::create([
            'organization_id' => $user->organization_id,
            'anthropic_api_key' => 'sk-ant-secret',
            'ai_provider' => 'anthropic',
        ]);
        app(CurrentOrganization::class)->forget();

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('settings.aiProvider', 'anthropic')
                ->where('settings.configured.anthropic_api_key', true)
                ->where('settings.configured.openai_api_key', false)
                ->where('capabilities.ai', true)
                // The raw secret must never reach the client.
                ->missing('settings.anthropic_api_key'));
    }

    public function test_saving_a_key_persists_it_encrypted(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put('/settings', [
            'ai_provider' => 'anthropic',
            'anthropic_api_key' => 'sk-ant-plaintext',
            'anthropic_model' => 'claude-sonnet-5',
        ])->assertRedirect()->assertSessionHas('status');

        app(CurrentOrganization::class)->set($user->organization_id);
        $row = OrganizationSetting::firstOrFail();

        // Decrypts through the model...
        $this->assertSame('sk-ant-plaintext', $row->anthropic_api_key);
        $this->assertSame('claude-sonnet-5', $row->anthropic_model);

        // ...but the stored ciphertext is not the plaintext.
        $raw = DB::table('organization_settings')
            ->where('organization_id', $user->organization_id)
            ->value('anthropic_api_key');
        $this->assertNotSame('sk-ant-plaintext', $raw);
        $this->assertNotNull($raw);
    }

    public function test_blank_keeps_existing_secret_and_remove_clears_it(): void
    {
        $user = $this->user();
        app(CurrentOrganization::class)->set($user->organization_id);
        OrganizationSetting::create([
            'organization_id' => $user->organization_id,
            'openai_api_key' => 'sk-keep',
        ]);
        app(CurrentOrganization::class)->forget();

        // Blank submission keeps it.
        $this->actingAs($user)->put('/settings', ['ai_provider' => 'openai']);
        app(CurrentOrganization::class)->set($user->organization_id);
        $this->assertSame('sk-keep', OrganizationSetting::firstOrFail()->openai_api_key);
        app(CurrentOrganization::class)->forget();

        // Remove flag clears it.
        $this->actingAs($user)->put('/settings', [
            'ai_provider' => 'openai',
            'remove' => ['openai_api_key'],
        ]);
        app(CurrentOrganization::class)->set($user->organization_id);
        $this->assertNull(OrganizationSetting::firstOrFail()->openai_api_key);
    }

    public function test_slack_webhook_must_be_a_url(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put('/settings', [
            'slack_webhook_url' => 'not-a-url',
        ])->assertSessionHasErrors('slack_webhook_url');
    }

    public function test_a_tenant_cannot_read_another_tenants_settings(): void
    {
        $userA = $this->user();
        app(CurrentOrganization::class)->set($userA->organization_id);
        OrganizationSetting::create([
            'organization_id' => $userA->organization_id,
            'anthropic_api_key' => 'sk-a-only',
        ]);
        app(CurrentOrganization::class)->forget();

        $userB = $this->user();

        $this->actingAs($userB)
            ->get('/settings')
            ->assertInertia(fn ($page) => $page
                ->where('settings.configured.anthropic_api_key', false));
    }
}
