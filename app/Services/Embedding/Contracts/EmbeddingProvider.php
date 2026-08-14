<?php

namespace App\Services\Embedding\Contracts;

/**
 * Provider-agnostic embeddings contract (P3). Separate from the text AI layer
 * because Anthropic has no embeddings API. Drivers (OpenAI, Voyage, …) turn
 * text into vectors for pgvector search.
 */
interface EmbeddingProvider
{
    /**
     * Embed a batch of texts, preserving order.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     *
     * @throws \App\Services\Embedding\EmbeddingException
     */
    public function embed(array $texts): array;

    public function name(): string;

    public function model(): string;

    public function dims(): int;
}
