<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// P1 — live Meta Marketing sync per tenant, every hour (skips unconnected
// orgs). A CLI process has no web-gateway timeout, so it always pulls the
// full config('meta.lookback_days') window — unlike the "Sync now" button,
// which is intentionally short (3 days) to stay inside the request timeout.
Schedule::command('meta:sync')->hourly()->withoutOverlapping();

// P2 Brand Spy — daily competitor Ad Library sync (no-ops when disabled).
Schedule::command('spy:sync')->dailyAt('03:00')->withoutOverlapping();

// P3 Ad Discovery — refresh embeddings after the spy sync.
Schedule::command('discovery:embed')->dailyAt('03:30')->withoutOverlapping();

// Fatigue & scaling alerts — daily detection.
Schedule::command('alerts:detect')->dailyAt('04:00')->timezone('Asia/Kuala_Lumpur');
