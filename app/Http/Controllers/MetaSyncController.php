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
    public function store(MetaSync $sync): RedirectResponse
    {
        if (! $sync->enabled()) {
            return redirect()->route('settings')->with('status',
                'Connect Meta first — add a System User token and ad account id below.');
        }

        try {
            $result = $sync->sync();
        } catch (MetaException $e) {
            return redirect()->back(fallback: route('analytics'))
                ->with('status', 'Meta sync failed — '.$e->getMessage());
        }

        return redirect()->route('analytics')->with('status', sprintf(
            'Synced from Meta — %d ads (%d new), %d daily metrics written. Recompute scores to update the report.',
            $result->adsCreated + $result->adsMatched,
            $result->adsCreated,
            $result->metricsCreated + $result->metricsUpdated,
        ));
    }
}
