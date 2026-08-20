<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Embedding provider (P3 Ad Discovery)
    |--------------------------------------------------------------------------
    |
    | Embeddings power pgvector semantic search. This is SEPARATE from the text
    | AI layer (config/ai.php) because Anthropic has no embeddings API. Swap via
    | EMBEDDING_PROVIDER; the dimension must match the model (and the DB column,
    | set at migration time), so changing models needs a new migration.
    |
    */

    'default' => env('EMBEDDING_PROVIDER', 'openai'),

    'model' => env('EMBEDDING_MODEL', 'text-embedding-3-small'),
    'dims' => (int) env('EMBEDDING_DIMS', 1536),

    'providers' => [
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com'),
        ],
        'voyage' => [
            'key' => env('VOYAGE_API_KEY'),
            'base_url' => env('VOYAGE_BASE_URL', 'https://api.voyageai.com'),
        ],
    ],

    'timeout' => (int) env('EMBEDDING_TIMEOUT', 60),
    'batch_size' => (int) env('EMBEDDING_BATCH_SIZE', 64),
];
