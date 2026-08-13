<?php

namespace App\Services\Ingest;

/**
 * Result of parsing a Meta CSV: normalized rows plus an honest account of what
 * was recognized, what was ignored, and what important columns are missing —
 * surfaced in the import preview so provenance is clear (§0).
 */
class ParsedCsv
{
    /**
     * @param  array<int, array<string, mixed>>  $rows  canonical field => normalized value
     * @param  array<string, string>  $mapping  canonical field => original header
     * @param  array<int, string>  $unknownHeaders  headers we ignored
     */
    public function __construct(
        public readonly array $rows,
        public readonly array $mapping,
        public readonly array $unknownHeaders,
    ) {}

    /** @return array<int, string> canonical fields we recognized */
    public function recognizedFields(): array
    {
        return array_keys($this->mapping);
    }

    /** @return array<int, string> important fields that were not found */
    public function missingImportantFields(): array
    {
        return array_values(array_diff(
            MetaHeaderMap::IMPORTANT_FIELDS,
            $this->recognizedFields(),
        ));
    }

    public function rowCount(): int
    {
        return count($this->rows);
    }
}
