<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meta Ad Library (P2 Brand Spy)
    |--------------------------------------------------------------------------
    |
    | The live sync is gated behind a feature flag until Ad Library API access
    | (app + identity verification + token) is granted. Until then the pillar
    | runs on saved/demo data. Note: in many countries (possibly MY) the Ad
    | Library API returns only political / social-issue ads — surfaced honestly
    | in the UI.
    |
    */

    'enabled' => (bool) env('FEATURE_META_AD_LIBRARY', false),

    'token' => env('META_AD_LIBRARY_TOKEN'),

    'base_url' => env('META_GRAPH_URL', 'https://graph.facebook.com'),
    'version' => env('META_GRAPH_VERSION', 'v19.0'),

    // ISO country code(s) the Ad Library search is scoped to.
    'country' => env('META_AD_LIBRARY_COUNTRY', 'MY'),

    'page_limit' => 25,   // ads per API page
    'max_pages' => 10,    // safety cap on pagination
];
