<?php

namespace Tests\Unit;

use App\Services\Ingest\MetaCsvParser;
use PHPUnit\Framework\TestCase;

class MetaCsvParserTest extends TestCase
{
    private function parse(string $csv)
    {
        return (new MetaCsvParser)->parseString($csv);
    }

    public function test_maps_varied_meta_headers_to_canonical_fields(): void
    {
        $csv = <<<'CSV'
        Ad name,Amount spent (MYR),Impressions,CTR (link click-through rate),ThruPlays,3-second video plays,Purchase ROAS (return on ad spend),Reporting starts
        Raya Sale,"1,234.50",100000,1.25%,5000,20000,4.2,2026-08-01
        CSV;

        $parsed = $this->parse($csv);

        $this->assertSame('Ad name', $parsed->mapping['ad_name']);
        $this->assertSame('Amount spent (MYR)', $parsed->mapping['spend']);
        $this->assertSame('CTR (link click-through rate)', $parsed->mapping['ctr_link']);
        $this->assertSame('3-second video plays', $parsed->mapping['video_3s']);
        $this->assertSame('Purchase ROAS (return on ad spend)', $parsed->mapping['roas']);
        $this->assertSame('Reporting starts', $parsed->mapping['date']);
        $this->assertEmpty($parsed->unknownHeaders);
    }

    public function test_normalizes_currency_percent_and_thousands(): void
    {
        $csv = <<<'CSV'
        Ad name,Amount spent (MYR),Impressions,CTR (link click-through rate),Purchase ROAS
        Raya Sale,"1,234.50",100000,1.25%,4.2
        Bundle,RM 800,50000,0.90%,2.1
        CSV;

        $rows = $this->parse($csv)->rows;

        $this->assertEquals(1234.50, $rows[0]['spend']);
        $this->assertSame(100000, $rows[0]['impressions']);
        $this->assertEquals(1.25, $rows[0]['ctr_link']);
        $this->assertEquals(4.2, $rows[0]['roas']);
        $this->assertEquals(800.0, $rows[1]['spend']);
    }

    public function test_blank_values_become_null_not_zero(): void
    {
        $csv = <<<'CSV'
        Ad name,Amount spent (MYR),Impressions,Purchase ROAS
        Raya Sale,1000,50000,-
        CSV;

        $rows = $this->parse($csv)->rows;

        $this->assertNull($rows[0]['roas']);
    }

    public function test_reports_missing_important_columns(): void
    {
        $csv = <<<'CSV'
        Ad name,Impressions
        Raya Sale,50000
        CSV;

        $parsed = $this->parse($csv);

        $this->assertContains('spend', $parsed->missingImportantFields());
        $this->assertNotContains('ad_name', $parsed->missingImportantFields());
    }

    public function test_ignores_unknown_columns(): void
    {
        $csv = <<<'CSV'
        Ad name,Amount spent (MYR),Frequency
        Raya Sale,1000,1.8
        CSV;

        $parsed = $this->parse($csv);

        $this->assertContains('Frequency', $parsed->unknownHeaders);
        $this->assertArrayNotHasKey('frequency', $parsed->rows[0]);
    }

    public function test_skips_rows_with_no_identifiable_ad(): void
    {
        $csv = <<<'CSV'
        Ad name,Amount spent (MYR),Impressions
        Raya Sale,1000,50000
        ,,
        CSV;

        $this->assertCount(1, $this->parse($csv)->rows);
    }
}
