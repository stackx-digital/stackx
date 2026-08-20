<?php

namespace App\Services\Ingest;

/** Summary of an import run, shown back to the user after ingest. */
class ImportResult
{
    public int $adsCreated = 0;

    public int $adsMatched = 0;

    public int $metricsCreated = 0;

    public int $metricsUpdated = 0;

    public int $rowsSkipped = 0;

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'ads_created' => $this->adsCreated,
            'ads_matched' => $this->adsMatched,
            'metrics_created' => $this->metricsCreated,
            'metrics_updated' => $this->metricsUpdated,
            'rows_skipped' => $this->rowsSkipped,
        ];
    }
}
