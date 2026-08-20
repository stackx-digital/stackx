<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Services\Marketing\MetaException;
use App\Services\Marketing\MetaSync;
use App\Support\CurrentOrganization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

/**
 * Scheduled live Meta Marketing sync, per tenant. Each org brings its own
 * System User token, so this iterates orgs, scopes to each, overlays that org's
 * Meta credentials onto config, and syncs. Orgs without Meta connected are
 * skipped cleanly.
 */
class MetaSyncCommand extends Command
{
    protected $signature = 'meta:sync {--days= : Lookback window in days (default from config)}';

    protected $description = 'Pull live ad-account performance from Meta for every connected tenant.';

    public function handle(CurrentOrganization $current, MetaSync $sync): int
    {
        $days = $this->option('days') !== null ? (int) $this->option('days') : null;

        // Deployment-wide env baseline, captured once so it can't be polluted by
        // a per-tenant overlay from a previous iteration.
        $envToken = config('meta.token');
        $envAccount = config('meta.ad_account_id');

        foreach (Organization::query()->orderBy('id')->get() as $org) {
            $current->set($org->id);

            $settings = OrganizationSetting::query()->first();
            // Explicitly set (including the env fallback) so one org's token
            // never bleeds into the next iteration.
            Config::set('meta.token', $settings?->meta_system_token ?: $envToken);
            Config::set('meta.ad_account_id', $settings?->meta_ad_account_id ?: $envAccount);

            if (! $sync->enabled()) {
                continue;
            }

            try {
                $result = $sync->sync($days);
                $this->info(sprintf(
                    '%s: %d ads (%d new), %d metrics.',
                    $org->name,
                    $result->adsCreated + $result->adsMatched,
                    $result->adsCreated,
                    $result->metricsCreated + $result->metricsUpdated,
                ));
            } catch (MetaException $e) {
                $this->warn("{$org->name}: {$e->getMessage()}");
            }
        }

        $current->forget();

        return self::SUCCESS;
    }
}
