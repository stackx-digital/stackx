<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\Alerts\AlertDetector;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Performance alerts (feature 3/4) — deterministic fatigue & scaling signals.
 */
class AlertController extends Controller
{
    public function index(): Response
    {
        $alerts = Alert::active()->with('ad')->latest()->get()->map(fn (Alert $a) => [
            'id' => $a->id,
            'type' => $a->type,
            'severity' => $a->severity,
            'title' => $a->title,
            'detail' => $a->detail,
            'ad' => $a->ad?->name,
            'createdAt' => $a->created_at?->diffForHumans(),
        ]);

        return Inertia::render('Alerts/Index', ['alerts' => $alerts]);
    }

    public function detect(AlertDetector $detector): RedirectResponse
    {
        $count = $detector->detect();

        return redirect()->route('alerts')->with('status',
            $count > 0 ? "Detection complete — {$count} alert(s) active." : 'No fatigue or scaling signals found.');
    }

    public function resolve(Alert $alert): RedirectResponse
    {
        $alert->update(['resolved_at' => now()]);

        return redirect()->route('alerts')->with('status', 'Alert dismissed.');
    }
}
