<?php

namespace App\Services\Ingest;

use League\Csv\Reader;

/**
 * Parses a Meta Ads Manager CSV export into normalized, canonical rows. Header
 * mapping is flexible (see MetaHeaderMap); values are normalized tolerantly
 * (see MetaValue). Unrecognized columns are ignored but reported.
 */
class MetaCsvParser
{
    private const INTEGER_FIELDS = ['impressions', 'reach', 'thruplays', 'video_3s'];

    private const TEXT_FIELDS = ['ad_name', 'meta_ad_id', 'ad_status'];

    public function __construct(private readonly MetaHeaderMap $headerMap = new MetaHeaderMap) {}

    public function parseFile(string $path): ParsedCsv
    {
        $reader = Reader::createFromPath($path, 'r');
        $reader->setHeaderOffset(0);

        return $this->parseReader($reader);
    }

    public function parseString(string $contents): ParsedCsv
    {
        $reader = Reader::fromString($contents);
        $reader->setHeaderOffset(0);

        return $this->parseReader($reader);
    }

    private function parseReader(Reader $reader): ParsedCsv
    {
        $headers = array_map('strval', $reader->getHeader());
        ['mapping' => $mapping, 'unknown' => $unknown] = $this->headerMap->build($headers);

        $rows = [];

        foreach ($reader->getRecords() as $record) {
            $row = [];

            foreach ($mapping as $field => $header) {
                $raw = $record[$header] ?? null;
                $row[$field] = $this->normalizeField($field, $raw);
            }

            // Skip blank/summary rows with no identifiable ad.
            if (($row['ad_name'] ?? '') === '' && ($row['meta_ad_id'] ?? '') === '') {
                continue;
            }

            $rows[] = $row;
        }

        return new ParsedCsv($rows, $mapping, $unknown);
    }

    private function normalizeField(string $field, mixed $raw): mixed
    {
        if (in_array($field, self::TEXT_FIELDS, true)) {
            return trim((string) ($raw ?? ''));
        }

        if ($field === 'date') {
            return MetaValue::date($raw);
        }

        if (in_array($field, self::INTEGER_FIELDS, true)) {
            return MetaValue::integer($raw);
        }

        // All remaining metric fields are decimals.
        return MetaValue::number($raw);
    }
}
