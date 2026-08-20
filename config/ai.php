<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI provider
    |--------------------------------------------------------------------------
    |
    | The AI layer is provider-agnostic (driver pattern, like Laravel's mail
    | and queue managers). Swap providers by changing AI_PROVIDER — no code
    | change. Supported: "anthropic", "openai", "ollama".
    |
    */

    'default' => env('AI_PROVIDER', 'anthropic'),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [

        'anthropic' => [
            'key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
            'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 4096),
            'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        ],

        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 4096),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com'),
        ],

        // Ollama Cloud — hosted, OpenAI-compatible. Use a "-cloud" model tag
        // (e.g. "gpt-oss:120b-cloud", "qwen3-coder:480b-cloud"); local/non-cloud
        // tags won't resolve against the hosted API.
        'ollama' => [
            'key' => env('OLLAMA_API_KEY'),
            'model' => env('OLLAMA_MODEL', 'gpt-oss:120b-cloud'),
            'max_tokens' => (int) env('OLLAMA_MAX_TOKENS', 4096),
            'base_url' => env('OLLAMA_BASE_URL', 'https://ollama.com'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Transport
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('AI_TIMEOUT', 60),
    'retries' => (int) env('AI_RETRIES', 2),
];
