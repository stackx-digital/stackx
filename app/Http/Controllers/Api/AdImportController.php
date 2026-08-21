<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Services\Ingest\AdMetricsImporter;
use App\Services\Ingest\MetaHeaderMap;
use App\Services\Ingest\ParsedCsv;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inbound push import (e.g. an n8n workflow pulling Meta on its own schedule
 * and uploading the rows here, instead of the app calling Meta directly).
 * Authenticated by EnsureApiToken (Bearer token, not a session). Rows go
 * through the SAME AdMetricsImporter the CSV and live-Meta-sync paths use, so
 * scoring treats push-imported data identically — idempotent, upsert by
 * (account, meta_ad_id or name) and (ad, date).
 */
class AdImportController extends Controller
{
    public function store(Request $request, AdMetricsImporter $importer): JsonResponse
    {
        $validated = $request->validate([
            'account_name' => ['nullable', 'string', 'max:255'],
            'rows' => ['required', 'array', 'min:1', 'max:5000'],
            'rows.*.ad_name' => ['nullable', 'string', 'max:255'],
            'rows.*.meta_ad_id' => ['nullable', 'string', 'max:100'],
            'rows.*.date' => ['nullable', 'string'],
            'rows.*.ad_status' => ['nullable', 'string', 'max:50'],
            'rows.*.spend' => ['nullable', 'numeric'],
            'rows.*.impressions' => ['nullable', 'numeric'],
            'rows.*.reach' => ['nullable', 'numeric'],
            'rows.*.ctr_all' => ['nullable', 'numeric'],
            'rows.*.ctr_link' => ['nullable', 'numeric'],
            'rows.*.cpc' => ['nullable', 'numeric'],
            'rows.*.cpm' => ['nullable', 'numeric'],
            'rows.*.thruplays' => ['nullable', 'numeric'],
            'rows.*.video_3s' => ['nullable', 'numeric'],
            'rows.*.results' => ['nullable', 'numeric'],
            'rows.*.cost_per_result' => ['nullable', 'numeric'],
            'rows.*.roas' => ['nullable', 'numeric'],
        ]);

        $rowsMissingIdentity = collect($validated['rows'])
            ->filter(fn ($row) => blank($row['ad_name'] ?? null) && blank($row['meta_ad_id'] ?? null));

        if ($rowsMissingIdentity->isNotEmpty()) {
            return response()->json([
                'message' => 'Every row needs an ad_name or meta_ad_id.',
            ], 422);
        }

        $accountName = trim((string) ($validated['account_name'] ?? 'n8n Import'));
        $account = AdAccount::firstOrCreate(
            ['name' => $accountName],
            ['currency' => 'MYR'],
        );

        $mapping = [];
        foreach (array_merge(['ad_name', 'meta_ad_id', 'ad_status', 'date'], MetaHeaderMap::METRIC_FIELDS) as $field) {
            $mapping[$field] = 'api:'.$field;
        }

        $result = $importer->import($account, new ParsedCsv($validated['rows'], $mapping, []));

        return response()->json([
            'account' => $account->name,
            'rows_received' => count($validated['rows']),
            'ads_created' => $result->adsCreated,
            'ads_matched' => $result->adsMatched,
            'metrics_created' => $result->metricsCreated,
            'metrics_updated' => $result->metricsUpdated,
            'rows_skipped' => $result->rowsSkipped,
        ]);
    }
}
