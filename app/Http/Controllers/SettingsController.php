<?php

namespace App\Http\Controllers;

use App\Models\OrganizationSetting;
use App\Services\Settings\TenantSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Per-tenant settings (SaaS Phase 2). Lets a tenant enter their own AI /
 * embedding / Meta / Slack credentials. Secrets are stored encrypted and are
 * NEVER sent back to the browser — the page only shows whether each is set.
 */
class SettingsController extends Controller
{
    /** Secret fields: write-only from the UI's perspective. */
    private const SECRETS = [
        'anthropic_api_key',
        'openai_api_key',
        'ollama_api_key',
        'voyage_api_key',
        'meta_ad_library_token',
        'meta_system_token',
        'meta_app_secret',
        'slack_webhook_url',
    ];

    public function edit(Request $request, TenantSettings $tenant): Response
    {
        $s = OrganizationSetting::query()->first();

        return Inertia::render('Settings/Index', [
            'settings' => [
                'aiProvider' => $s?->ai_provider,
                'anthropicModel' => $s?->anthropic_model,
                'openaiModel' => $s?->openai_model,
                'ollamaModel' => $s?->ollama_model,
                'embeddingProvider' => $s?->embedding_provider,
                'metaAdAccountId' => $s?->meta_ad_account_id,
                'metaAppId' => $s?->meta_app_id,
                // Presence only — never the values themselves.
                'configured' => collect(self::SECRETS)
                    ->mapWithKeys(fn ($k) => [$k => filled($s?->{$k})])
                    ->all(),
                'apiTokenSet' => filled($s?->api_token_hash),
            ],
            'capabilities' => $tenant->capabilities(),
            'defaults' => [
                'anthropicModel' => config('ai.providers.anthropic.model'),
                'openaiModel' => config('ai.providers.openai.model'),
                // Whether the deployment supplies a fallback key via env.
                'envAiKey' => filled(config('ai.providers.anthropic.key'))
                    || filled(config('ai.providers.openai.key')),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ai_provider' => ['nullable', 'in:anthropic,openai,ollama'],
            'anthropic_model' => ['nullable', 'string', 'max:120'],
            'openai_model' => ['nullable', 'string', 'max:120'],
            'ollama_model' => ['nullable', 'string', 'max:120'],
            'embedding_provider' => ['nullable', 'in:openai,voyage'],
            'meta_ad_account_id' => ['nullable', 'string', 'max:1000'],
            'meta_app_id' => ['nullable', 'string', 'max:64'],

            'anthropic_api_key' => ['nullable', 'string', 'max:300'],
            'openai_api_key' => ['nullable', 'string', 'max:300'],
            'ollama_api_key' => ['nullable', 'string', 'max:300'],
            'voyage_api_key' => ['nullable', 'string', 'max:300'],
            'meta_ad_library_token' => ['nullable', 'string', 'max:500'],
            'meta_system_token' => ['nullable', 'string', 'max:500'],
            'meta_app_secret' => ['nullable', 'string', 'max:255'],
            'slack_webhook_url' => ['nullable', 'string', 'url', 'max:500'],

            // Explicit "remove this saved credential" toggles.
            'remove' => ['array'],
            'remove.*' => ['in:'.implode(',', self::SECRETS)],
        ]);

        $settings = OrganizationSetting::query()->firstOrNew([]);

        // Non-secret preferences always reflect the submitted form.
        $settings->ai_provider = $validated['ai_provider'] ?? null;
        $settings->anthropic_model = $validated['anthropic_model'] ?? null;
        $settings->openai_model = $validated['openai_model'] ?? null;
        $settings->ollama_model = $validated['ollama_model'] ?? null;
        $settings->embedding_provider = $validated['embedding_provider'] ?? null;
        $settings->meta_ad_account_id = $validated['meta_ad_account_id'] ?? null;
        $settings->meta_app_id = $validated['meta_app_id'] ?? null;

        // Secrets: a new value replaces, a remove flag clears, blank keeps.
        $remove = $validated['remove'] ?? [];
        foreach (self::SECRETS as $field) {
            if (in_array($field, $remove, true)) {
                $settings->{$field} = null;
            } elseif (filled($validated[$field] ?? null)) {
                $settings->{$field} = $validated[$field];
            }
        }

        $settings->save();

        return back()->with('status', 'Settings saved.');
    }
}
