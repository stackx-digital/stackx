<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Report;
use App\Services\Reporting\ReportBuilder;
use App\Services\Reporting\SlackNotifier;
use App\Support\CurrentOrganization;
use Illuminate\Console\Command;

/**
 * Scheduled weekly Slack report (§5, P5). Builds a snapshot per org, saves it as
 * a shareable report, and posts the summary to Slack with the public link.
 * No-ops when no webhook is configured.
 */
class ReportsSlackCommand extends Command
{
    protected $signature = 'reports:slack';

    protected $description = 'Post the weekly performance summary to Slack.';

    public function handle(ReportBuilder $builder, SlackNotifier $slack, CurrentOrganization $org): int
    {
        if (! $slack->configured()) {
            $this->warn('SLACK_WEBHOOK_URL not set — skipping.');

            return self::SUCCESS;
        }

        foreach (Organization::all() as $organization) {
            $org->set($organization->id);

            $payload = $builder->build();
            $title = 'Weekly report — '.now()->format('d M Y');

            $report = Report::create([
                'organization_id' => $organization->id,
                'title' => $title,
                'token' => Report::newToken(),
                'payload' => $payload,
            ]);

            $sent = $slack->sendReport($payload, $title, url('/r/'.$report->token));
            $this->line(($sent ? '<info>sent</info>' : '<comment>failed</comment>')." — {$organization->name}");
        }

        return self::SUCCESS;
    }
}
