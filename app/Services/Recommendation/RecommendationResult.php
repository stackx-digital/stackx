<?php

namespace App\Services\Recommendation;

/** Outcome of a recommendation run. Failures are non-fatal (§5). */
class RecommendationResult
{
    public int $written = 0;

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
