<?php

namespace App\Http\Controllers;

use App\Services\Settings\ApiTokenManager;
use Illuminate\Http\RedirectResponse;

/**
 * Generate/revoke the tenant's inbound API token (Settings → API access), used
 * by external automations (e.g. n8n) to push ad data via POST /api/v1/ads/import
 * instead of the app pulling from Meta directly.
 */
class ApiTokenController extends Controller
{
    public function store(ApiTokenManager $tokens): RedirectResponse
    {
        $token = $tokens->generate();

        // Shown once — never persisted in plaintext, never retrievable again.
        return back()->with('apiToken', $token)
            ->with('status', 'New API token generated — copy it now, it won’t be shown again.');
    }

    public function destroy(ApiTokenManager $tokens): RedirectResponse
    {
        $tokens->revoke();

        return back()->with('status', 'API token revoked.');
    }
}
