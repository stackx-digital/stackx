<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-tenant credentials + provider preferences (SaaS Phase 2). Secret columns
 * are transparently encrypted at rest via the `encrypted` cast. Org-scoped like
 * every other tenant table, so a query only ever returns the current org's row.
 */
class OrganizationSetting extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'ai_provider',
        'anthropic_api_key',
        'anthropic_model',
        'openai_api_key',
        'openai_model',
        'embedding_provider',
        'voyage_api_key',
        'meta_ad_library_token',
        'meta_system_token',
        'meta_ad_account_id',
        'slack_webhook_url',
    ];

    protected $hidden = [
        'anthropic_api_key',
        'openai_api_key',
        'voyage_api_key',
        'meta_ad_library_token',
        'meta_system_token',
        'slack_webhook_url',
    ];

    protected function casts(): array
    {
        return [
            'anthropic_api_key' => 'encrypted',
            'openai_api_key' => 'encrypted',
            'voyage_api_key' => 'encrypted',
            'meta_ad_library_token' => 'encrypted',
            'meta_system_token' => 'encrypted',
            'slack_webhook_url' => 'encrypted',
        ];
    }
}
