<?php

namespace Tests\Unit;

use App\Services\Scoring\Percentile;
use PHPUnit\Framework\TestCase;

class PercentileTest extends TestCase
{
    public function test_higher_is_better_spreads_across_range(): void
    {
        $r = Percentile::rank(['a' => 10.0, 'b' => 20.0, 'c' => 30.0]);

        $this->assertGreaterThan($r['a'], $r['b']);
        $this->assertGreaterThan($r['b'], $r['c']);
        $this->assertSame(50, $r['b']); // middle of three
    }

    public function test_ties_share_a_percentile(): void
    {
        $r = Percentile::rank(['a' => 10.0, 'b' => 10.0]);

        $this->assertSame($r['a'], $r['b']);
        $this->assertSame(50, $r['a']);
    }

    public function test_single_value_is_neutral_midpoint(): void
    {
        $this->assertSame(['a' => 50], Percentile::rank(['a' => 7.5]));
    }

    public function test_lower_is_better_inverts(): void
    {
        // Cost metric: 10 is better than 20 → 10 ranks higher.
        $r = Percentile::rank(['cheap' => 10.0, 'pricey' => 20.0], higherIsBetter: false);

        $this->assertGreaterThan($r['pricey'], $r['cheap']);
    }

    public function test_empty_returns_empty(): void
    {
        $this->assertSame([], Percentile::rank([]));
    }
}
