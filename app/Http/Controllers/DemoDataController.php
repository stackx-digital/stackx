<?php

namespace App\Http\Controllers;

use Database\Seeders\DemoAdsSeeder;
use Illuminate\Http\RedirectResponse;

/**
 * One-click demo data so the app is usable with zero setup (§7 M2). Loads a
 * clearly-labelled "Demo" ad account with realistic MY-context ads. Idempotent.
 */
class DemoDataController extends Controller
{
    public function store(DemoAdsSeeder $seeder): RedirectResponse
    {
        $seeder->run();

        // back() so it works from both Analytics and the onboarding welcome page;
        // falls back to the analytics report when there's no referer.
        return redirect()->back(fallback: route('analytics'))
            ->with('status', 'Demo data loaded — 7 sample ads in the “Demo — Raya Campaign” account.');
    }
}
