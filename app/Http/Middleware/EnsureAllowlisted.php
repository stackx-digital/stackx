<?php

namespace App\Http\Middleware;

use App\Support\Allowlist;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks authenticated users whose email is not on the STACKx allowlist.
 * They keep their session but are bounced to /not-authorized. Defense in
 * depth on top of the login-time check.
 */
class EnsureAllowlisted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! Allowlist::allows($user->email)) {
            return redirect()->route('not-authorized');
        }

        return $next($request);
    }
}
