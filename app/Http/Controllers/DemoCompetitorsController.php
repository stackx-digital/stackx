<?php

namespace App\Http\Controllers;

use Database\Seeders\DemoCompetitorsSeeder;
use Illuminate\Http\RedirectResponse;

/** One-click demo competitors so Brand Spy is usable without API access (P2). */
class DemoCompetitorsController extends Controller
{
    public function store(DemoCompetitorsSeeder $seeder): RedirectResponse
    {
        $seeder->run();

        return redirect()->route('spy')
            ->with('status', 'Demo competitors loaded — 3 brands with sample ads.');
    }
}
