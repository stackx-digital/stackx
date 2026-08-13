<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AiInsightController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CompetitorController;
use App\Http\Controllers\DemoCompetitorsController;
use App\Http\Controllers\DemoDataController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\SpyController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
| STACKx Ad Intelligence — internal cockpit. Public routes: only /login and
| the signed magic-link verify. Everything else requires an authenticated,
| allowlisted session.
*/

Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'analytics' : 'login');
});

// Authenticated + allowlisted app shell (the 5 pillars).
Route::middleware(['auth', 'allowlisted'])->group(function () {
    // P1 — Creative Analytics (report M4). Scored ads from M3.
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    // CSV ingest (M2).
    Route::get('/analytics/import', [ImportController::class, 'show'])->name('import.show');
    Route::post('/analytics/import/preview', [ImportController::class, 'preview'])->name('import.preview');
    Route::post('/analytics/import', [ImportController::class, 'store'])->name('import.store');
    Route::post('/analytics/demo', [DemoDataController::class, 'store'])->name('demo.load');

    // Scoring (M3).
    Route::post('/analytics/score', [ScoreController::class, 'store'])->name('score.recompute');

    // Ad detail for the drawer (M4).
    Route::get('/analytics/ads/{ad}', [AdController::class, 'show'])->name('ads.show');

    // AI insights: tags + recommendations (M5).
    Route::post('/analytics/ai', [AiInsightController::class, 'store'])->name('ai.insights');

    // P2 — Brand Spy.
    Route::get('/spy', [SpyController::class, 'index'])->name('spy');
    Route::post('/spy/competitors', [CompetitorController::class, 'store'])->name('competitors.store');
    Route::post('/spy/competitors/{competitor}/sync', [CompetitorController::class, 'sync'])->name('competitors.sync');
    Route::post('/spy/demo', [DemoCompetitorsController::class, 'store'])->name('spy.demo');

    // P3–P5 — placeholders until their milestones.
    Route::get('/discovery', fn () => Inertia::render('Discovery/Index'))->name('discovery');
    Route::get('/create', fn () => Inertia::render('Create/Index'))->name('create');
    Route::get('/reports', fn () => Inertia::render('Reports/Index'))->name('reports');
});

// Authenticated but off-allowlist: valid session, no app access.
Route::get('/not-authorized', fn () => Inertia::render('NotAuthorized'))
    ->middleware('auth')
    ->name('not-authorized');

require __DIR__.'/auth.php';
