<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AiInsightController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardItemController;
use App\Http\Controllers\CompetitorController;
use App\Http\Controllers\CreateController;
use App\Http\Controllers\DemoCompetitorsController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\PublicReportController;
use App\Http\Controllers\ReportController;
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

    // Creative Library — swipe boards (P6).
    Route::get('/library', [BoardController::class, 'index'])->name('library');
    Route::post('/library', [BoardController::class, 'store'])->name('boards.store');
    Route::get('/library/{board}', [BoardController::class, 'show'])->name('boards.show');
    Route::delete('/library/{board}', [BoardController::class, 'destroy'])->name('boards.destroy');
    Route::post('/library/{board}/items', [BoardItemController::class, 'store'])->name('board-items.store');
    Route::delete('/library/items/{item}', [BoardItemController::class, 'destroy'])->name('board-items.destroy');

    // Performance alerts (fatigue & scaling).
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts');
    Route::post('/alerts/detect', [AlertController::class, 'detect'])->name('alerts.detect');
    Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

    // P2 — Brand Spy.
    Route::get('/spy', [SpyController::class, 'index'])->name('spy');
    Route::post('/spy/competitors', [CompetitorController::class, 'store'])->name('competitors.store');
    Route::post('/spy/competitors/{competitor}/sync', [CompetitorController::class, 'sync'])->name('competitors.sync');
    Route::post('/spy/demo', [DemoCompetitorsController::class, 'store'])->name('spy.demo');

    // P3 — Ad Discovery (pgvector semantic search).
    Route::get('/discovery', [DiscoveryController::class, 'index'])->name('discovery');
    Route::post('/discovery/embed', [DiscoveryController::class, 'embed'])->name('discovery.embed');

    // P4 — Ad Creation (AI copy variations + creative briefs).
    Route::get('/create', [CreateController::class, 'index'])->name('create');
    Route::post('/create', [CreateController::class, 'store'])->name('create.generate');
    Route::post('/create/brief', [CreateController::class, 'brief'])->name('create.brief');

    // P5 — Reports (shareable snapshots + Slack summary).
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::post('/reports/slack', [ReportController::class, 'slack'])->name('reports.slack');
    Route::delete('/reports/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');
});

// Public, read-only shared report (no auth — token is the secret).
Route::get('/r/{token}', [PublicReportController::class, 'show'])->name('report.public');

// Authenticated but off-allowlist: valid session, no app access.
Route::get('/not-authorized', fn () => Inertia::render('NotAuthorized'))
    ->middleware('auth')
    ->name('not-authorized');

require __DIR__.'/auth.php';
