<?php

namespace App\Http\Middleware;

use App\Services\Settings\ApiTokenManager;
use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stateless auth for inbound machine-to-machine pushes (e.g. n8n), as an
 * alternative to a browser session. Resolves the tenant from a Bearer token
 * (`Authorization: Bearer <token>`) instead of a logged-in user, and scopes
 * the request to that org exactly like ApplyTenantSettings does for the web.
 */
class EnsureApiToken
{
    public function __construct(
        private ApiTokenManager $tokens,
        private CurrentOrganization $current,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $organizationId = $token !== null ? $this->tokens->organizationIdFor($token) : null;

        if ($organizationId === null) {
            return response()->json(['message' => 'Invalid or missing API token.'], 401);
        }

        $this->current->set($organizationId);

        return $next($request);
    }
}
