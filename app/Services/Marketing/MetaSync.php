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
 * Idempotent: re-running updates in place.
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
     * @throws MetaException
     */
    public function sync(?int $lookbackDays = null): ImportResult
    {
        $insights = $this->client->insights($lookbackDays);

        $rows = [];
        foreach ($insights as $insight) {
            $rows[] = $insight->toRow();
        }

        $account = $this->resolveAccount();

        // Build a ParsedCsv so we can reuse the CSV importer verbatim.
        $mapping = [];
        foreach (array_merge(['ad_name', 'meta_ad_id', 'date'], MetaHeaderMap::METRIC_FIELDS) as $field) {
            $mapping[$field] = 'meta:'.$field;
        }

        return $this->importer->import($account, new ParsedCsv($rows, $mapping, []));
    }

    /** One stable "Meta — {account}" ad account per tenant, keyed by account id. */
    private function resolveAccount(): AdAccount
    {
        $accountId = $this->client->accountId();

        return AdAccount::updateOrCreate(
            ['meta_ad_account_id' => $accountId],
            ['name' => 'Meta — '.$accountId, 'currency' => 'MYR'],
        );
    }
}
