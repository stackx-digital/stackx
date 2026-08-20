<?php

namespace App\Console\Commands;

use App\Models\Competitor;
use App\Services\AdLibrary\AdLibraryDisabledException;
use App\Services\AdLibrary\CompetitorSync;
use App\Services\AdLibrary\MetaAdLibraryClient;
use Illuminate\Console\Command;

/**
 * Scheduled competitor sync (P2). Runs system-wide (all orgs), so it bypasses
 * the org global scope. No-ops cleanly when the Ad Library feature is off.
 */
class SpySyncCommand extends Command
{
    protected $signature = 'spy:sync';

    protected $description = 'Sync competitor ads from the Meta Ad Library.';

    public function handle(MetaAdLibraryClient $client, CompetitorSync $sync): int
    {
        if (! $client->enabled()) {
            $this->warn('Ad Library sync is disabled (FEATURE_META_AD_LIBRARY) — skipping.');

            return self::SUCCESS;
        }

        $competitors = Competitor::withoutGlobalScope('organization')->get();

        foreach ($competitors as $competitor) {
            try {
                $result = $sync->sync($competitor);
                $this->info(sprintf(
                    '%s: +%d new, ~%d updated, -%d stopped.',
                    $competitor->name, $result->created, $result->updated, $result->deactivated,
                ));
            } catch (AdLibraryDisabledException $e) {
                $this->warn($e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
