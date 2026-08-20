<?php

namespace App\Services\Embedding\Drivers;

use App\Services\Embedding\Contracts\EmbeddingProvider;
use App\Services\Embedding\EmbeddingException;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Voyage AI embeddings (Anthropic's recommended embeddings partner). */
class VoyageEmbeddingProvider implements EmbeddingProvider
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private array $config,
        private string $model,
        private int $dims,
        private int $timeout = 60,
    ) {}

    public function name(): string
    {
        return 'voyage';
    }

    public function model(): string
    {
        return $this->model;
    }

    public function dims(): int
    {
        return $this->dims;
    }

    public function embed(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $key = $this->config['key'] ?? null;
        if (blank($key)) {
            throw new EmbeddingException('Missing VOYAGE_API_KEY for embeddings.');
        }

        $base = rtrim((string) ($this->config['base_url'] ?? 'https://api.voyageai.com'), '/');

        try {
            $response = Http::withToken($key)
                ->timeout($this->timeout)
                ->retry(2, 500)
                ->post("{$base}/v1/embeddings", [
                    'model' => $this->model,
                    'input' => array_values($texts),
                ]);
        } catch (Throwable $e) {
            throw new EmbeddingException("Voyage embeddings request failed: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new EmbeddingException("Voyage embeddings error ({$response->status()}): ".$response->body());
        }

        $out = [];
        foreach ($response->json('data', []) as $row) {
            $out[$row['index'] ?? count($out)] = array_map('floatval', $row['embedding'] ?? []);
        }
        ksort($out);

        return array_values($out);
    }
}
