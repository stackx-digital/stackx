<?php

namespace App\Services\Settings;

use App\Models\OrganizationSetting;
use Illuminate\Support\Str;

/**
 * Inbound API tokens for machine-to-machine pushes (e.g. an n8n workflow that
 * pulls Meta data on its own schedule and uploads it here). Only a SHA-256
 * hash is ever stored — the plaintext is returned once, at generation time,
 * and cannot be recovered afterward. Losing it means generating a new one.
 */
class ApiTokenManager
{
    private const PREFIX = 'stackx_';

    /** Generate a new token for the current org, replacing any existing one. */
    public function generate(): string
    {
        $token = self::PREFIX.Str::random(40);

        OrganizationSetting::query()->firstOrNew([])
            ->fill(['api_token_hash' => $this->hash($token)])
            ->save();

        return $token;
    }

    public function revoke(): void
    {
        $settings = OrganizationSetting::query()->first();
        $settings?->update(['api_token_hash' => null]);
    }

    public function hasToken(): bool
    {
        return OrganizationSetting::query()->whereNotNull('api_token_hash')->exists();
    }

    /** Resolve the organization_id a presented token belongs to, or null. */
    public function organizationIdFor(string $token): ?int
    {
        return OrganizationSetting::query()
            ->withoutGlobalScope('organization')
            ->where('api_token_hash', $this->hash($token))
            ->value('organization_id');
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
