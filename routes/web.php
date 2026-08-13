<?php

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
    // P1 — Creative Analytics (data ingest M2, scoring M3, report M4).
    Route::get('/analytics', fn () => Inertia::render('Analytics/Index'))
        ->name('analytics');

    // P2–P5 — placeholders until their milestones.
    Route::get('/spy', fn () => Inertia::render('Spy/Index'))->name('spy');
    Route::get('/discovery', fn () => Inertia::render('Discovery/Index'))->name('discovery');
    Route::get('/create', fn () => Inertia::render('Create/Index'))->name('create');
    Route::get('/reports', fn () => Inertia::render('Reports/Index'))->name('reports');
});

// Authenticated but off-allowlist: valid session, no app access.
Route::get('/not-authorized', fn () => Inertia::render('NotAuthorized'))
    ->middleware('auth')
    ->name('not-authorized');

require __DIR__.'/auth.php';
