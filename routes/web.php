<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AiInsightController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardItemController;
use App\Http\Controllers\CompetitorController;
use App\Http\Controllers\CreateController;
use App\Http\Controllers\DemoCompetitorsController;
use App\Http\Controllers\DemoDataController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\MetaSyncController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PublicReportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SpyController;
use App\Http\Controllers\VisionTagController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
| STACKx Ad Intelligence — multi-tenant SaaS. Public routes: signup, login,
| password reset, and shared report tokens. Every app route requires an
| authenticated, email-verified session; all data is scoped to the user's org.
*/

Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'analytics' : 'login');
});

// Authenticated + email-verified app shell (the 5 pillars). Each user only
// ever sees their own organization's data (CurrentOrganization + global scope),
// and the tenant's BYO credentials are overlaid onto config (ApplyTenantSettings).
Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // P1 — Creative Analytics (report M4). Scored ads from M3.
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    // CSV ingest (M2).
    Route::get('/analytics/import', [ImportController::class, 'show'])->name('import.show');
    Route::post('/analytics/import/preview', [ImportController::class, 'preview'])->name('import.preview');
    Route::post('/analytics/import', [ImportController::class, 'store'])->name('import.store');
    Route::post('/analytics/demo', [DemoDataController::class, 'store'])->name('demo.load');

    // Live Meta Marketing sync (P1 live) — pull own ad-account performance.
    Route::post('/analytics/meta/sync', [MetaSyncController::class, 'store'])->name('meta.sync');

    // Scoring (M3).
    Route::post('/analytics/score', [ScoreController::class, 'store'])->name('score.recompute');

    // Ad detail for the drawer (M4).
    Route::get('/analytics/ads/{ad}', [AdController::class, 'show'])->name('ads.show');

    // Vision tagging from an uploaded creative (P4 Phase 2).
    Route::post('/analytics/ads/{ad}/vision-tag', [VisionTagController::class, 'store'])->name('ads.vision');

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

    // Per-tenant settings — BYO API keys (Phase 2).
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Inbound API token — for machine-to-machine pushes (e.g. n8n).
    Route::post('/settings/api-token', [ApiTokenController::class, 'store'])->name('settings.api-token.store');
    Route::delete('/settings/api-token', [ApiTokenController::class, 'destroy'])->name('settings.api-token.destroy');

    // Onboarding — guided welcome checklist for new tenants (Phase 3).
    Route::get('/welcome', [OnboardingController::class, 'show'])->name('welcome');
    Route::post('/welcome/complete', [OnboardingController::class, 'complete'])->name('welcome.complete');
});

// Public, read-only shared report (no auth — token is the secret).
Route::get('/r/{token}', [PublicReportController::class, 'show'])->name('report.public');

require __DIR__.'/auth.php';
