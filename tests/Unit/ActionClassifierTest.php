<?php

namespace Tests\Unit;

use App\Services\Scoring\ActionClassifier;
use App\Services\Scoring\AdAggregate;
use PHPUnit\Framework\TestCase;

class ActionClassifierTest extends TestCase
{
    private function roasAgg(int $id, float $roas, float $spend): AdAggregate
    {
        return new AdAggregate($id, $spend, 100000, 0.2, 0.1, 1.0, 1.5, $roas, null, $roas * $spend, 100.0);
    }

    private function cprAgg(int $id, float $cpr, float $spend, float $results): AdAggregate
    {
        return new AdAggregate($id, $spend, 100000, 0.2, 0.1, 1.0, 1.5, null, $cpr, null, $results);
    }

    public function test_roas_scale_keep_cut_and_low_spend_floor(): void
    {
        $aggs = [
            1 => $this->roasAgg(1, 6.0, 4000),  // strong + volume → scale
            2 => $this->roasAgg(2, 4.0, 2000),  // near blended → keep
            3 => $this->roasAgg(3, 0.5, 2000),  // weak + volume → cut
            4 => $this->roasAgg(4, 8.0, 50),    // great but tiny spend → keep (floor)
        ];

        $r = (new ActionClassifier)->classify($aggs);

        $this->assertSame('scale', $r[1]['action']);
        $this->assertSame('keep', $r[2]['action']);
        $this->assertSame('cut', $r[3]['action']);
        $this->assertSame('keep', $r[4]['action']);
        $this->assertStringContainsString('floor', $r[4]['reason']);
    }

    public function test_falls_back_to_cost_per_result_when_no_roas(): void
    {
        $aggs = [
            1 => $this->cprAgg(1, 5.0, 2000, 400),   // cheap → scale
            2 => $this->cprAgg(2, 40.0, 2000, 50),   // expensive → cut
        ];

        $r = (new ActionClassifier)->classify($aggs);

        $this->assertSame('scale', $r[1]['action']);
        $this->assertSame('cut', $r[2]['action']);
    }

    public function test_no_conversion_signal_keeps_everything(): void
    {
        $aggs = [
            1 => new AdAggregate(1, 1000, 100000, 0.2, 0.1, 1.0, 1.5, null, null, null, null),
        ];

        $r = (new ActionClassifier)->classify($aggs);

        $this->assertSame('keep', $r[1]['action']);
    }
}
