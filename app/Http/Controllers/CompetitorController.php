<?php

namespace App\Http\Controllers;

use App\Models\Competitor;
use App\Services\AdLibrary\AdLibraryDisabledException;
use App\Services\AdLibrary\CompetitorSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Manage tracked competitors and trigger Ad Library syncs (P2). Org scoping is
 * automatic via the Competitor global scope.
 */
class CompetitorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'meta_page_id' => ['nullable', 'string', 'max:64'],
            'search_terms' => ['nullable', 'string', 'max:255'],
        ]);

        Competitor::create($validated);

        return redirect()->route('spy')->with('status', "Tracking “{$validated['name']}”.");
    }

    public function sync(Competitor $competitor, CompetitorSync $sync): RedirectResponse
    {
        try {
            $result = $sync->sync($competitor);
        } catch (AdLibraryDisabledException $e) {
            return redirect()->route('spy')->with('status', $e->getMessage());
        }

        return redirect()->route('spy')->with('status', sprintf(
            'Synced “%s” — %d new, %d updated, %d stopped (from %d fetched).',
            $competitor->name,
            $result->created,
            $result->updated,
            $result->deactivated,
            $result->fetched,
        ));
    }
}
