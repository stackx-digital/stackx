<?php

namespace App\Services\Tagging;

/** Outcome of a tagging run. Failures are non-fatal (§5 graceful degrade). */
class TaggingResult
{
    public int $tagged = 0;

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
