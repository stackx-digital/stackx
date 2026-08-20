<?php

namespace App\Services\Marketing;

use App\Models\AdAccount;
use App\Services\Ingest\AdMetricsImporter;
use App\Services\Ingest\ImportResult;
use App\Services\Ingest\MetaHeaderMap;
use App\Services\Ingest\ParsedCsv;

/**
 * Pulls live ad-level daily insights from the Meta Marketing API and upserts
 * them through the same importer the CSV path uses — so live data and imported
 * data are identical in shape and the scoring engine treats them the same.
 * Syncs every connected ad account (an agency can wire up many); idempotent.
 */
class MetaSync
{
    public function __construct(
        private readonly MetaMarketingClient $client,
        private readonly AdMetricsImporter $importer,
    ) {}

    public function enabled(): bool
    {
        return $this->client->enabled();
    }

    /**
     * Sync all connected ad accounts. Returns a combined result across them.
     *
     * @throws MetaException
     */
    public function sync(?int $lookbackDays = null): ImportResult
    {
        $accounts = $this->client->accountIds();

        if ($accounts === []) {
            throw new MetaException('No Meta ad account id set — add one in Settings.');
        }

        $combined = new ImportResult;

        foreach ($accounts as $accountId) {
            $this->syncAccount($accountId, $lookbackDays, $combined);
        }

        return $combined;
    }

    private function syncAccount(string $accountId, ?int $lookbackDays, ImportResult $combined): void
    {
        $rows = [];
        foreach ($this->client->insights($accountId, $lookbackDays) as $insight) {
            $rows[] = $insight->toRow();
        }

        $account = AdAccount::updateOrCreate(
            ['meta_ad_account_id' => $accountId],
            ['name' => 'Meta — '.$accountId, 'currency' => 'MYR'],
        );

        $result = $this->importer->import($account, new ParsedCsv($rows, $this->mapping(), []));

        $combined->adsCreated += $result->adsCreated;
        $combined->adsMatched += $result->adsMatched;
        $combined->metricsCreated += $result->metricsCreated;
        $combined->metricsUpdated += $result->metricsUpdated;
        $combined->rowsSkipped += $result->rowsSkipped;
    }

    /** @return array<string, string> canonical field => synthetic header */
    private function mapping(): array
    {
        $mapping = [];
        foreach (array_merge(['ad_name', 'meta_ad_id', 'date'], MetaHeaderMap::METRIC_FIELDS) as $field) {
            $mapping[$field] = 'meta:'.$field;
        }

        return $mapping;
    }
}
