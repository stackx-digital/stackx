<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\Reporting\ReportBuilder;
use App\Services\Reporting\SlackNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P5 Reports — create shareable snapshots and post the summary to Slack. Each
 * report has a random public token (/r/{token}); deleting it revokes the link.
 */
class ReportController extends Controller
{
    public function index(SlackNotifier $slack): Response
    {
        $reports = Report::with('creator')->latest()->get()->map(fn (Report $r) => [
            'id' => $r->id,
            'title' => $r->title,
            'url' => url('/r/'.$r->token),
            'createdBy' => $r->creator?->name,
            'createdAt' => $r->created_at?->diffForHumans(),
        ]);

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'slackConfigured' => $slack->configured(),
        ]);
    }

    public function store(Request $request, ReportBuilder $builder): RedirectResponse
    {
        $report = Report::create([
            'title' => 'Report — '.now()->format('d M Y, g:ia'),
            'token' => Report::newToken(),
            'payload' => $builder->build(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('reports')
            ->with('status', 'Report created — shareable at '.url('/r/'.$report->token));
    }

    public function slack(Request $request, ReportBuilder $builder, SlackNotifier $slack): RedirectResponse
    {
        $report = Report::create([
            'title' => 'Report — '.now()->format('d M Y, g:ia'),
            'token' => Report::newToken(),
            'payload' => $builder->build(),
            'created_by' => $request->user()->id,
        ]);

        $sent = $slack->sendReport($report->payload, $report->title, url('/r/'.$report->token));

        return redirect()->route('reports')->with('status', $sent
            ? 'Report created and posted to Slack.'
            : 'Report created. Slack not sent — set SLACK_WEBHOOK_URL.');
    }

    public function destroy(Report $report): RedirectResponse
    {
        $report->delete();

        return redirect()->route('reports')->with('status', 'Report revoked — the public link no longer works.');
    }
}
