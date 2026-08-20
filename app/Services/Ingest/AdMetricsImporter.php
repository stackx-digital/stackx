<?php

namespace App\Services\Ingest;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\AdMetric;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Upserts parsed CSV rows into ads + ad_metrics (§5). Idempotent: ads are keyed
 * by (account, meta_ad_id) or (account, name); metrics by (ad, date). Re-running
 * the same export updates in place rather than duplicating.
 */
class AdMetricsImporter
{
    /**
     * @param  string|null  $defaultDate  Y-m-d used for rows without a date
     *                                     column (lifetime exports). Defaults to today.
     */
    public function import(AdAccount $account, ParsedCsv $parsed, ?string $defaultDate = null): ImportResult
    {
        $result = new ImportResult;
        $fallbackDate = $defaultDate ?? Carbon::now()->toDateString();

        DB::transaction(function () use ($account, $parsed, $fallbackDate, $result) {
            foreach ($parsed->rows as $row) {
                $name = trim((string) ($row['ad_name'] ?? ''));
                $metaId = trim((string) ($row['meta_ad_id'] ?? ''));

                if ($name === '' && $metaId === '') {
                    $result->rowsSkipped++;

                    continue;
                }

                $ad = $this->upsertAd($account, $name, $metaId, $row, $result);
                $this->upsertMetric($ad->id, $row, $fallbackDate, $result);
            }
        });

        return $result;
    }

    private function upsertAd(AdAccount $account, string $name, string $metaId, array $row, ImportResult $result): Ad
    {
        $match = $metaId !== ''
            ? ['ad_account_id' => $account->id, 'meta_ad_id' => $metaId]
            : ['ad_account_id' => $account->id, 'name' => $name];

        $values = ['organization_id' => $account->organization_id];
        if ($name !== '') {
            $values['name'] = $name;
        }
        if ($metaId !== '') {
            $values['meta_ad_id'] = $metaId;
        }
        if (array_key_exists('ad_status', $row) && $row['ad_status'] !== '') {
            $values['status'] = $row['ad_status'];
        }

        $ad = Ad::updateOrCreate($match, $values);

        $ad->wasRecentlyCreated ? $result->adsCreated++ : $result->adsMatched++;

        return $ad;
    }

    private function upsertMetric(int $adId, array $row, string $fallbackDate, ImportResult $result): void
    {
        // Match on a normalized Carbon date so re-imports upsert in place across
        // both sqlite and Postgres (the 'date' cast serializes with a time part).
        $date = Carbon::parse($row['date'] ?? $fallbackDate)->startOfDay();

        $values = [];
        foreach (MetaHeaderMap::METRIC_FIELDS as $field) {
            if (array_key_exists($field, $row)) {
                $values[$field] = $row[$field];
            }
        }

        $metric = AdMetric::updateOrCreate(
            ['ad_id' => $adId, 'date' => $date],
            $values,
        );

        $metric->wasRecentlyCreated ? $result->metricsCreated++ : $result->metricsUpdated++;
    }
}
