<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * STACKx team email allowlist (§7 M1, §5). Layer 1 of two: this app-layer
 * gate is enforced at login and by the EnsureAllowlisted middleware. Layer 2
 * (org-scoped row policies) arrives with the data model in M2.
 */
class Allowlist
{
    /**
     * Is this email permitted to use the app? Matching is case-insensitive
     * and supports full emails plus "@domain" globs. Fails closed on an
     * empty/misconfigured allowlist.
     */
    public static function allows(?string $email): bool
    {
        if (blank($email)) {
            return false;
        }

        $normalized = Str::lower(trim($email));
        $domain = Str::of($normalized)->after('@')->prepend('@')->value();

        $entries = collect(config('stackx.allowed_emails'))
            ->map(fn ($e) => Str::lower(trim($e)))
            ->filter();

        if ($entries->isEmpty()) {
            return false;
        }

        return $entries->contains(
            fn ($entry) => str_starts_with($entry, '@')
                ? $entry === $domain
                : $entry === $normalized,
        );
    }
}
