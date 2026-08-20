<?php

namespace App\Services\Scoring;

use App\Models\Ad;
use App\Models\AdAccount;

/**
 * Aggregates each ad's daily ad_metrics into canonical raw rates (§3). Rates
 * are rebuilt from summed components (e.g. link CTR = summed link clicks /
 * summed impressions) so aggregation is impression-weighted, not a naive
 * average of daily percentages. A rate is null when its source is absent for
 * the ad across every day — never a fabricated 0.
 */
class MetricAggregator
{
    /** @return array<int, AdAggregate> keyed by ad id */
    public function forAccount(AdAccount $account): array
    {
        $ads = Ad::with('metrics')
            ->where('ad_account_id', $account->id)
            ->get();

        return $this->aggregate($ads);
    }

    /**
     * @param  iterable<Ad>  $ads  each with its metrics relation loaded
     * @return array<int, AdAggregate>
     */
    public function aggregate(iterable $ads): array
    {
        $out = [];

        foreach ($ads as $ad) {
            $spend = 0.0;
            $impr = 0;
            $video3s = 0;
            $thru = 0;
            $linkClicks = 0.0;
            $allClicks = 0.0;
            $revenue = 0.0;
            $results = 0.0;

            $hasVideo3s = $hasThru = $hasLinkCtr = $hasCtrAll = $hasRoas = $hasResults = false;

            foreach ($ad->metrics as $m) {
                $spend += (float) ($m->spend ?? 0);
                $dayImpr = (int) ($m->impressions ?? 0);
                $impr += $dayImpr;

                if ($m->video_3s !== null) {
                    $hasVideo3s = true;
                    $video3s += (int) $m->video_3s;
                }
                if ($m->thruplays !== null) {
                    $hasThru = true;
                    $thru += (int) $m->thruplays;
                }
                if ($m->ctr_link !== null && $dayImpr > 0) {
                    $hasLinkCtr = true;
                    $linkClicks += (float) $m->ctr_link / 100 * $dayImpr;
                }
                if ($m->ctr_all !== null && $dayImpr > 0) {
                    $hasCtrAll = true;
                    $allClicks += (float) $m->ctr_all / 100 * $dayImpr;
                }
                if ($m->roas !== null) {
                    $hasRoas = true;
                    $revenue += (float) $m->roas * (float) ($m->spend ?? 0);
                }
                if ($m->results !== null) {
                    $hasResults = true;
                    $results += (float) $m->results;
                }
            }

            $out[$ad->id] = new AdAggregate(
                adId: $ad->id,
                spend: round($spend, 2),
                impressions: $impr,
                hookRate: $hasVideo3s && $impr > 0 ? $video3s / $impr : null,
                holdRate: $hasThru && $impr > 0 ? $thru / $impr : null,
                linkCtr: $hasLinkCtr && $impr > 0 ? $linkClicks / $impr * 100 : null,
                ctrAll: $hasCtrAll && $impr > 0 ? $allClicks / $impr * 100 : null,
                roas: $hasRoas && $spend > 0 ? $revenue / $spend : null,
                costPerResult: $hasResults && $results > 0 ? $spend / $results : null,
                revenue: $hasRoas ? round($revenue, 2) : null,
                results: $hasResults ? $results : null,
            );
        }

        return $out;
    }
}
