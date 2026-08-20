<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meta Marketing API — live creative performance sync (P1)
    |--------------------------------------------------------------------------
    |
    | Pulls a tenant's OWN ad-account insights (ad-level, daily) into ads +
    | ad_metrics, so Creative Analytics runs on live data instead of CSV. This
    | is separate from the Ad Library (config/ad_library.php), which reads
    | competitors' public ads.
    |
    | Credentials are BYO per tenant (Settings → Meta): a System User token +
    | ad account id. The env values below are only a deployment-wide fallback,
    | overlaid per request by ApplyTenantSettings.
    |
    */

    'token' => env('META_SYSTEM_TOKEN'),

    // Ad account id — with or without the "act_" prefix; normalized in the client.
    'ad_account_id' => env('META_AD_ACCOUNT_ID'),

    // App credentials. The secret lets the client send an appsecret_proof, which
    // Meta expects for server-side calls and which resolves "(#200) Provide
    // valid app ID" for apps in Development mode.
    'app_id' => env('META_APP_ID'),
    'app_secret' => env('META_APP_SECRET'),

    'base_url' => env('META_GRAPH_URL', 'https://graph.facebook.com'),
    'version' => env('META_GRAPH_VERSION', 'v21.0'),

    // How many days back to pull on a sync (Meta caps insights at 37 months).
    'lookback_days' => (int) env('META_LOOKBACK_DAYS', 30),

    'page_limit' => (int) env('META_PAGE_LIMIT', 200), // rows per API page
    'max_pages' => (int) env('META_MAX_PAGES', 25),    // safety cap on pagination
    'timeout' => (int) env('META_TIMEOUT', 60),
];
