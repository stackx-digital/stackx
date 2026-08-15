<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// P2 Brand Spy — daily competitor Ad Library sync (no-ops when disabled).
Schedule::command('spy:sync')->dailyAt('03:00')->withoutOverlapping();

// P3 Ad Discovery — refresh embeddings after the spy sync.
Schedule::command('discovery:embed')->dailyAt('03:30')->withoutOverlapping();

// P5 Reports — weekly Slack summary, Monday 9am MYT (no-ops without a webhook).
Schedule::command('reports:slack')->weeklyOn(1, '09:00')->timezone('Asia/Kuala_Lumpur');

// Fatigue & scaling alerts — daily detection + Slack digest.
Schedule::command('alerts:detect')->dailyAt('04:00')->timezone('Asia/Kuala_Lumpur');
