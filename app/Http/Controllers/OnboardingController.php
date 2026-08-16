<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Services\Settings\TenantSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * SaaS Phase 3 — a guided welcome checklist for a fresh tenant: add an AI key,
 * then load demo data or import real ads. Steps are derived from actual state
 * (not a stored flag), so the checklist always reflects reality. Finishing (or
 * skipping) stamps the org's onboarded_at.
 */
class OnboardingController extends Controller
{
    public function show(Request $request, TenantSettings $tenant): Response
    {
        $capabilities = $tenant->capabilities();

        return Inertia::render('Onboarding/Welcome', [
            'steps' => [
                'aiConfigured' => $capabilities['ai'],
                'hasData' => Ad::query()->exists(),
            ],
            'completed' => $request->user()->organization?->onboarded_at !== null,
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $organization = $request->user()->organization;

        if ($organization !== null && $organization->onboarded_at === null) {
            $organization->forceFill(['onboarded_at' => now()])->save();
        }

        return redirect()->route('analytics')
            ->with('status', 'You’re all set — welcome to STACKx.');
    }
}
