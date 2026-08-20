<?php

namespace App\Services\Embedding\Drivers;

use App\Services\Embedding\Contracts\EmbeddingProvider;
use App\Services\Embedding\EmbeddingException;
use Illuminate\Support\Facades\Http;
use Throwable;

/** OpenAI embeddings (text-embedding-3-*). */
class OpenAiEmbeddingProvider implements EmbeddingProvider
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
        return 'openai';
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
            throw new EmbeddingException('Missing OPENAI_API_KEY for embeddings.');
        }

        $base = rtrim((string) ($this->config['base_url'] ?? 'https://api.openai.com'), '/');

        try {
            $response = Http::withToken($key)
                ->timeout($this->timeout)
                ->retry(2, 500)
                ->post("{$base}/v1/embeddings", [
                    'model' => $this->model,
                    'input' => array_values($texts),
                ]);
        } catch (Throwable $e) {
            throw new EmbeddingException("OpenAI embeddings request failed: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new EmbeddingException("OpenAI embeddings error ({$response->status()}): ".$response->body());
        }

        $data = $response->json('data', []);

        // Preserve input order via the returned index.
        $out = [];
        foreach ($data as $row) {
            $out[$row['index'] ?? count($out)] = array_map('floatval', $row['embedding'] ?? []);
        }
        ksort($out);

        return array_values($out);
    }
}
