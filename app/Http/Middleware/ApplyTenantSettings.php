<?php

namespace App\Http\Middleware;

use App\Services\Settings\TenantSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Overlays the authenticated tenant's stored credentials onto runtime config
 * before the request is handled, so every downstream service uses that org's
 * BYO keys (falling back to env). Runs on the authenticated app routes.
 */
class ApplyTenantSettings
{
    public function __construct(private TenantSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->settings->apply();

        return $next($request);
    }
}
