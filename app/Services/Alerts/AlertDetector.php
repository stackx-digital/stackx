<?php

namespace App\Services\Alerts;

use App\Models\Ad;
use App\Models\Alert;
use Illuminate\Support\Collection;

/**
 * Deterministic performance-alert detection (feature 3/4). Compares the early
 * vs late half of each ad's daily metrics to flag:
 *   - fatigue: frequency rising while link-CTR falls (creative wearing out)
 *   - scale:   strong, non-declining ROAS above the blended benchmark
 * Computed maths — never AI. Alerts dedupe per (ad, type) while unresolved.
 */
class AlertDetector
{
    private const MIN_DAYS = 4;

    public function detect(): int
    {
        $ads = Ad::with('metrics')->get();
        $blended = $this->blendedRoas($ads);
        $touched = 0;

        foreach ($ads as $ad) {
            $days = $ad->metrics->sortBy('date')->values();
            if ($days->count() < self::MIN_DAYS) {
                continue;
            }

            $half = intdiv($days->count(), 2);
            $early = $days->slice(0, $half);
            $late = $days->slice($half);

            $touched += $this->checkFatigue($ad, $early, $late);
            $touched += $this->checkScale($ad, $early, $late, $blended);
        }

        return $touched;
    }

    private function checkFatigue(Ad $ad, Collection $early, Collection $late): int
    {
        $earlyCtr = $this->avg($early, 'ctr_link');
        $lateCtr = $this->avg($late, 'ctr_link');
        $earlyFreq = $this->frequency($early);
        $lateFreq = $this->frequency($late);

        if ($earlyCtr === null || $lateCtr === null || $earlyFreq === null || $lateFreq === null) {
            return 0;
        }

        $freqRising = $lateFreq >= $earlyFreq * 1.1;
        $ctrFalling = $lateCtr <= $earlyCtr * 0.85;

        if (! ($freqRising && $ctrFalling)) {
            return 0;
        }

        $ctrDrop = (int) round((1 - $lateCtr / $earlyCtr) * 100);
        $freqUp = (int) round(($lateFreq / $earlyFreq - 1) * 100);

        $this->upsert($ad, 'fatigue', $ctrDrop >= 30 ? 'high' : 'medium',
            'Creative fatiguing',
            "Link CTR down {$ctrDrop}% while frequency up {$freqUp}% — refresh the creative.",
            ['ctr_drop_pct' => $ctrDrop, 'freq_up_pct' => $freqUp],
        );

        return 1;
    }

    private function checkScale(Ad $ad, Collection $early, Collection $late, float $blended): int
    {
        $earlyRoas = $this->avg($early, 'roas');
        $lateRoas = $this->avg($late, 'roas');

        if ($lateRoas === null || $blended <= 0) {
            return 0;
        }

        $strong = $lateRoas >= $blended * 1.2;
        $notDeclining = $earlyRoas === null || $lateRoas >= $earlyRoas * 0.9;

        if (! ($strong && $notDeclining)) {
            return 0;
        }

        $this->upsert($ad, 'scale', 'medium',
            'Scaling opportunity',
            sprintf('ROAS %.1f vs blended %.1f and holding — room to scale spend.', $lateRoas, $blended),
            ['late_roas' => round($lateRoas, 2), 'blended' => round($blended, 2)],
        );

        return 1;
    }

    private function upsert(Ad $ad, string $type, string $severity, string $title, string $detail, array $context): void
    {
        Alert::updateOrCreate(
            ['ad_id' => $ad->id, 'type' => $type, 'resolved_at' => null],
            [
                'organization_id' => $ad->organization_id,
                'severity' => $severity,
                'title' => $title,
                'detail' => $detail,
                'context' => $context,
            ],
        );
    }

    /** @param Collection<int, \App\Models\AdMetric> $rows */
    private function avg(Collection $rows, string $field): ?float
    {
        $values = $rows->pluck($field)->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);

        return $values->isEmpty() ? null : (float) $values->avg();
    }

    /** Frequency = summed impressions / summed reach over the window. */
    private function frequency(Collection $rows): ?float
    {
        $impr = $rows->pluck('impressions')->filter()->sum();
        $reach = $rows->pluck('reach')->filter()->sum();

        return $reach > 0 ? $impr / $reach : null;
    }

    private function blendedRoas(Collection $ads): float
    {
        $revenue = 0.0;
        $spend = 0.0;

        foreach ($ads as $ad) {
            foreach ($ad->metrics as $m) {
                if ($m->roas !== null && $m->spend !== null) {
                    $revenue += (float) $m->roas * (float) $m->spend;
                    $spend += (float) $m->spend;
                }
            }
        }

        return $spend > 0 ? $revenue / $spend : 0.0;
    }
}
