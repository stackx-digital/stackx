<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Organization;
use App\Services\Alerts\AlertDetector;
use App\Support\CurrentOrganization;
use Illuminate\Console\Command;

/**
 * Scheduled fatigue & scaling detection (feature 3/4). Runs per org and
 * writes active alerts — surfaced in the /alerts UI.
 */
class AlertsDetectCommand extends Command
{
    protected $signature = 'alerts:detect';

    protected $description = 'Detect creative fatigue and scaling opportunities from daily metrics.';

    public function handle(AlertDetector $detector, CurrentOrganization $org): int
    {
        foreach (Organization::all() as $organization) {
            $org->set($organization->id);
            $detector->detect();

            $active = Alert::active()->with('ad')->get();
            $this->info("{$organization->name}: {$active->count()} active alert(s).");
        }

        return self::SUCCESS;
    }
}
