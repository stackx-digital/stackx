<?php

use App\Http\Controllers\Api\AdImportController;
use Illuminate\Support\Facades\Route;

/*
| Stateless, token-authenticated API for machine-to-machine pushes (e.g. an
| n8n workflow that pulls Meta on its own schedule and uploads the rows
| here). Auth is a Bearer token (EnsureApiToken), not a browser session.
*/

Route::middleware('api.token')->group(function () {
    Route::post('/v1/ads/import', [AdImportController::class, 'store'])->name('api.ads.import');
});
