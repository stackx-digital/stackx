<?php

namespace App\Http\Controllers;

use App\Services\Marketing\MetaException;
use App\Services\Marketing\MetaSync;
use Illuminate\Http\RedirectResponse;

/**
 * Pull live creative performance from the tenant's Meta ad account (P1 live
 * sync). Degrades honestly when Meta isn't connected or the API rejects the
 * request — the CSV path always remains available.
 */
class MetaSyncController extends Controller
{
    /**
     * On-demand lookback, kept short so the request finishes well inside the
     * web server's gateway timeout — a 30-day, ad-level, daily pull can page
     * through hundreds of rows and blow past it. The scheduled meta:sync
     * command (a CLI process, no gateway involved) still pulls the full
     * config('meta.lookback_days') window every night.
     */
    private const ON_DEMAND_LOOKBACK_DAYS = 3;

    public function store(MetaSync $sync): RedirectResponse
    {
        if (! $sync->enabled()) {
            return redirect()->route('settings')->with('status',
                'Connect Meta first — add a System User token and ad account id below.');
        }

        try {
            $result = $sync->sync(self::ON_DEMAND_LOOKBACK_DAYS);
        } catch (MetaException $e) {
            return redirect()->back(fallback: route('analytics'))
                ->with('status', 'Meta sync failed — '.$e->getMessage());
        }

        return redirect()->route('analytics')->with('status', sprintf(
            'Synced from Meta (last %d days) — %d ads (%d new), %d daily metrics written. Recompute scores to update the report. The full history syncs automatically every night.',
            self::ON_DEMAND_LOOKBACK_DAYS,
            $result->adsCreated + $result->adsMatched,
            $result->adsCreated,
            $result->metricsCreated + $result->metricsUpdated,
        ));
    }
}
