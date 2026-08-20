<?php

namespace Tests\Unit;

use App\Services\Scoring\AdAggregate;
use App\Services\Scoring\ScoreCalculator;
use PHPUnit\Framework\TestCase;

class ScoreCalculatorTest extends TestCase
{
    private function agg(
        int $id,
        float $spend = 1000,
        int $impr = 100000,
        ?float $hook = null,
        ?float $hold = null,
        ?float $link = null,
        ?float $ctrAll = null,
        ?float $roas = null,
        ?float $cpr = null,
    ): AdAggregate {
        return new AdAggregate($id, $spend, $impr, $hook, $hold, $link, $ctrAll, $roas, $cpr, $roas ? $roas * $spend : null, 100);
    }

    public function test_full_data_scores_spread_and_rank_correctly(): void
    {
        $aggs = [
            1 => $this->agg(1, hook: 0.30, hold: 0.14, link: 2.0, roas: 6.0),
            2 => $this->agg(2, hook: 0.20, hold: 0.10, link: 1.2, roas: 3.0),
            3 => $this->agg(3, hook: 0.10, hold: 0.05, link: 0.6, roas: 1.0),
        ];

        $f = (new ScoreCalculator)->funnelScores($aggs);

        foreach (['hook', 'watch', 'click', 'convert'] as $stage) {
            $this->assertNotNull($f[1][$stage]);
            $this->assertGreaterThan($f[3][$stage], $f[1][$stage]);
            $this->assertGreaterThanOrEqual(0, $f[3][$stage]);
            $this->assertLessThanOrEqual(100, $f[1][$stage]);
        }
    }

    public function test_convert_is_na_when_no_roas_or_cpr_account_wide(): void
    {
        $aggs = [
            1 => $this->agg(1, hook: 0.30, roas: null, cpr: null),
            2 => $this->agg(2, hook: 0.20, roas: null, cpr: null),
        ];

        $f = (new ScoreCalculator)->funnelScores($aggs);

        $this->assertNull($f[1]['convert']);
        $this->assertNull($f[2]['convert']);
    }

    public function test_hook_falls_back_to_ctr_all_when_no_video(): void
    {
        // No hook_rate anywhere (image ads), but CTR (all) present.
        $aggs = [
            1 => $this->agg(1, hook: null, ctrAll: 2.0),
            2 => $this->agg(2, hook: null, ctrAll: 1.0),
        ];

        $f = (new ScoreCalculator)->funnelScores($aggs);

        $this->assertNotNull($f[1]['hook']);
        $this->assertGreaterThan($f[2]['hook'], $f[1]['hook']);
    }

    public function test_watch_falls_back_to_hook_score(): void
    {
        // No hold_rate anywhere → Watch copies Hook.
        $aggs = [
            1 => $this->agg(1, hook: 0.30, hold: null),
            2 => $this->agg(2, hook: 0.10, hold: null),
        ];

        $f = (new ScoreCalculator)->funnelScores($aggs);

        $this->assertSame($f[1]['hook'], $f[1]['watch']);
        $this->assertSame($f[2]['hook'], $f[2]['watch']);
    }

    public function test_convert_uses_inverse_cpr_when_no_roas(): void
    {
        // Lower cost-per-result is better → ad 1 (cheaper) scores higher.
        $aggs = [
            1 => $this->agg(1, roas: null, cpr: 5.0),
            2 => $this->agg(2, roas: null, cpr: 20.0),
        ];

        $f = (new ScoreCalculator)->funnelScores($aggs);

        $this->assertGreaterThan($f[2]['convert'], $f[1]['convert']);
    }
}
