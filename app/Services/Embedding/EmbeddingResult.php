<?php

namespace App\Services\Embedding;

/** Outcome of an indexing run. Failures are non-fatal (§5). */
class EmbeddingResult
{
    public int $embedded = 0;

    public int $skipped = 0;

    public int $batches = 0;

    public int $failedBatches = 0;

    /** @var array<int, string> */
    public array $errors = [];

    public function failed(string $message): void
    {
        $this->failedBatches++;
        $this->errors[] = $message;
    }
}
