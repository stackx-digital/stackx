<?php

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Services\Scoring\ScoringService;
use Illuminate\Http\RedirectResponse;

/**
 * Recompute deterministic scores (§3) for every ad account in the org. Scores
 * are percentiles within each account, so each account is scored on its own.
 */
class ScoreController extends Controller
{
    public function store(ScoringService $scoring): RedirectResponse
    {
        $accounts = AdAccount::all();
        $total = 0;

        foreach ($accounts as $account) {
            $total += $scoring->scoreAccount($account);
        }

        return redirect()->route('analytics')->with('status', sprintf(
            'Scored %d ad(s) across %d account(s).',
            $total,
            $accounts->count(),
        ));
    }
}
