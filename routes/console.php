<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// P2 Brand Spy — daily competitor Ad Library sync (no-ops when disabled).
Schedule::command('spy:sync')->dailyAt('03:00')->withoutOverlapping();
