<?php

use App\Http\Controllers\Auth\MagicLinkController;
use Illuminate\Support\Facades\Route;

/*
| Passwordless auth (§5). Magic link only — no registration, no password
| reset. The allowlist is enforced inside the controller and by the
| 'allowlisted' middleware on app routes.
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [MagicLinkController::class, 'create'])->name('login');
    Route::post('login', [MagicLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');

    Route::get('login/{user}/verify', [MagicLinkController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('login.verify');
});

Route::post('logout', [MagicLinkController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
