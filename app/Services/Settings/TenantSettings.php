<?php

namespace App\Services\Settings;

use App\Models\OrganizationSetting;
use App\Support\CurrentOrganization;
use Illuminate\Support\Facades\Config;

/**
 * Bridges a tenant's stored, encrypted credentials (OrganizationSetting) onto
 * the runtime config the services already read (config/ai.php, embedding.php,
 * ad_library.php, services.php). Everything downstream — AiManager,
 * EmbeddingManager, SlackNotifier, MetaAdLibraryClient — stays untouched: it
 * keeps reading config(), which we've overlaid per request.
 *
 * Only non-empty tenant values override the env defaults, so a tenant that
 * hasn't entered a given key inherits whatever the deployment configured (or
 * nothing, and the feature degrades honestly).
 */
class TenantSettings
{
    public function __construct(private CurrentOrganization $current) {}

    /** The current org's settings row, if any. */
    public function current(): ?OrganizationSetting
    {
        if ($this->current->id() === null) {
            return null;
        }

        return OrganizationSetting::query()->first();
    }

    /** Overlay the current tenant's credentials onto runtime config. */
    public function apply(): void
    {
        $settings = $this->current();

        if ($settings === null) {
            return;
        }

        // --- AI text layer (config/ai.php) ---
        $this->set('ai.default', $settings->ai_provider);
        $this->set('ai.providers.anthropic.key', $settings->anthropic_api_key);
        $this->set('ai.providers.anthropic.model', $settings->anthropic_model);
        $this->set('ai.providers.openai.key', $settings->openai_api_key);
        $this->set('ai.providers.openai.model', $settings->openai_model);

        // --- Embeddings (config/embedding.php). OpenAI reuses the AI key. ---
        $this->set('embedding.default', $settings->embedding_provider);
        $this->set('embedding.providers.openai.key', $settings->openai_api_key);
        $this->set('embedding.providers.voyage.key', $settings->voyage_api_key);

        // --- Brand Spy (config/ad_library.php). A token turns the flag on. ---
        if (filled($settings->meta_ad_library_token)) {
            Config::set('ad_library.token', $settings->meta_ad_library_token);
            Config::set('ad_library.enabled', true);
        }

        // --- Reports Slack (config/services.php) ---
        $this->set('services.slack.webhook', $settings->slack_webhook_url);
    }

    /**
     * Which capabilities this tenant has credentials for — drives honest
     * "not configured" states in the UI without ever exposing the secrets.
     *
     * @return array<string, bool>
     */
    public function capabilities(): array
    {
        $s = $this->current();

        $aiProvider = $s?->ai_provider ?? config('ai.default');
        $aiReady = $aiProvider === 'openai'
            ? filled($s?->openai_api_key) || filled(config('ai.providers.openai.key'))
            : filled($s?->anthropic_api_key) || filled(config('ai.providers.anthropic.key'));

        return [
            'ai' => $aiReady,
            'embedding' => filled($s?->openai_api_key) || filled($s?->voyage_api_key)
                || filled(config('embedding.providers.openai.key')),
            'adLibrary' => filled($s?->meta_ad_library_token) || filled(config('ad_library.token')),
            'slack' => filled($s?->slack_webhook_url) || filled(config('services.slack.webhook')),
        ];
    }

    private function set(string $key, ?string $value): void
    {
        if (filled($value)) {
            Config::set($key, $value);
        }
    }
}
