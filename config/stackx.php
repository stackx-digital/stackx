<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product identity
    |--------------------------------------------------------------------------
    |
    | STACKx Ad Intelligence is a multi-tenant SaaS. Anyone can self-register;
    | each signup provisions its own organization (tenant) and all data is
    | scoped to it. No allowlist — access is governed by auth + email
    | verification.
    |
    */

    'app_name' => env('STACKX_APP_NAME', 'STACKx Ad Intelligence'),

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
