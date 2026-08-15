<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Organization;
use App\Services\Alerts\AlertDetector;
use App\Services\Reporting\SlackNotifier;
use App\Support\CurrentOrganization;
use Illuminate\Console\Command;

/**
 * Scheduled fatigue & scaling detection (feature 3/4). Runs per org; posts a
 * short Slack digest of active alerts when a webhook is configured.
 */
class AlertsDetectCommand extends Command
{
    protected $signature = 'alerts:detect';

    protected $description = 'Detect creative fatigue and scaling opportunities from daily metrics.';

    public function handle(AlertDetector $detector, SlackNotifier $slack, CurrentOrganization $org): int
    {
        foreach (Organization::all() as $organization) {
            $org->set($organization->id);
            $detector->detect();

            $active = Alert::active()->with('ad')->get();
            $this->info("{$organization->name}: {$active->count()} active alert(s).");

            if ($active->isNotEmpty() && $slack->configured()) {
                $lines = $active->take(10)->map(
                    fn (Alert $a) => '• ['.strtoupper($a->type).'] '.($a->ad?->name ?? 'Account').' — '.$a->detail,
                )->all();
                $slack->sendText("*STACKx alerts — {$organization->name}*\n".implode("\n", $lines));
            }
        }

        return self::SUCCESS;
    }
}
