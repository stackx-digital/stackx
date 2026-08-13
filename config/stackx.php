<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Team access allowlist
    |--------------------------------------------------------------------------
    |
    | STACKx Ad Intelligence is an internal tool — no public signup. Access is
    | restricted to this allowlist (comma-separated in STACKX_ALLOWED_EMAILS).
    | Entries may be a full email (alice@stackx.my) or a domain glob
    | (@stackx.my). An empty list fails closed — nobody gets in.
    |
    */

    'allowed_emails' => array_filter(
        array_map('trim', explode(',', (string) env('STACKX_ALLOWED_EMAILS', ''))),
    ),

    /*
    |--------------------------------------------------------------------------
    | Magic link lifetime (minutes)
    |--------------------------------------------------------------------------
    */

    'magic_link_ttl' => (int) env('STACKX_MAGIC_LINK_TTL', 15),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    | The app reports in Malaysian Ringgit (RM). Kept here so formatting has a
    | single source of truth server-side.
    */

    'currency' => 'MYR',
    'locale' => 'en-MY',
];
