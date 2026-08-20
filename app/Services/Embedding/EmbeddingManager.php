<?php

namespace App\Services\Embedding;

use App\Services\Embedding\Contracts\EmbeddingProvider;
use App\Services\Embedding\Drivers\OpenAiEmbeddingProvider;
use App\Services\Embedding\Drivers\VoyageEmbeddingProvider;
use Illuminate\Support\Manager;

/**
 * Resolves the configured embedding provider (driver pattern, like AiManager).
 * Swap with EMBEDDING_PROVIDER. Facade-style calls proxy to the default driver.
 *
 * @mixin EmbeddingProvider
 */
class EmbeddingManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('embedding.default', 'openai');
    }

    protected function createOpenaiDriver(): EmbeddingProvider
    {
        return new OpenAiEmbeddingProvider(
            (array) $this->config->get('embedding.providers.openai', []),
            (string) $this->config->get('embedding.model'),
            (int) $this->config->get('embedding.dims'),
            (int) $this->config->get('embedding.timeout', 60),
        );
    }

    protected function createVoyageDriver(): EmbeddingProvider
    {
        return new VoyageEmbeddingProvider(
            (array) $this->config->get('embedding.providers.voyage', []),
            (string) $this->config->get('embedding.model'),
            (int) $this->config->get('embedding.dims'),
            (int) $this->config->get('embedding.timeout', 60),
        );
    }
}
