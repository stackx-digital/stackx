<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\AdMetric;
use App\Models\Organization;
use App\Support\CurrentOrganization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Realistic Malaysian-context demo ads so the app is usable with zero setup
 * (§7 M2). Spend is in RM; metrics are deliberately spread so the percentile
 * scoring (M3) has a meaningful distribution. This is demo data — clearly a
 * "Demo" account, and idempotent (safe to re-run).
 */
class DemoAdsSeeder extends Seeder
{
    /**
     * @var array<int, array{name:string, spend:float, impressions:int, video_3s:int, thruplays:int, ctr_link:float, results:float, roas:float}>
     */
    private array $ads = [
        ['name' => 'Raya Sale 2026 | Video | Hook-Discount',      'spend' => 4200.00, 'impressions' => 512000, 'video_3s' => 168000, 'thruplays' => 74000, 'ctr_link' => 2.10, 'results' => 640, 'roas' => 6.8],
        ['name' => 'Baju Raya Collection | Reels | UGC',          'spend' => 3800.00, 'impressions' => 431000, 'video_3s' => 152000, 'thruplays' => 69000, 'ctr_link' => 1.95, 'results' => 520, 'roas' => 5.9],
        ['name' => 'Buka Puasa Deals | Video | Problem-Solution', 'spend' => 2600.00, 'impressions' => 288000, 'video_3s' => 82000,  'thruplays' => 34000, 'ctr_link' => 1.42, 'results' => 300, 'roas' => 3.4],
        ['name' => 'Ramadan Bundle | Carousel | Testimonial',     'spend' => 2100.00, 'impressions' => 240000, 'video_3s' => 41000,  'thruplays' => 12000, 'ctr_link' => 1.15, 'results' => 210, 'roas' => 2.6],
        ['name' => 'Duit Raya Giveaway | Video | Curiosity',      'spend' => 1900.00, 'impressions' => 355000, 'video_3s' => 96000,  'thruplays' => 28000, 'ctr_link' => 0.98, 'results' => 150, 'roas' => 1.9],
        ['name' => 'Merdeka Promo | Image | Urgency',             'spend' => 1500.00, 'impressions' => 176000, 'video_3s' => 0,      'thruplays' => 0,     'ctr_link' => 0.74, 'results' => 88,  'roas' => 1.2],
        ['name' => 'Free Shipping MY | Image | Offer',            'spend' => 1200.00, 'impressions' => 132000, 'video_3s' => 0,      'thruplays' => 0,     'ctr_link' => 0.61, 'results' => 52,  'roas' => 0.8],
    ];

    public function run(): void
    {
        $org = Organization::firstOrCreate(['slug' => 'stackx'], ['name' => 'STACKx']);
        app(CurrentOrganization::class)->set($org->id);

        $account = AdAccount::firstOrCreate(
            ['name' => 'Demo — Raya Campaign'],
            ['currency' => 'MYR', 'meta_ad_account_id' => 'act_demo_0001'],
        );

        $dates = collect(range(0, 2))->map(
            fn ($d) => Carbon::today()->subDays($d)->toDateString(),
        );

        foreach ($this->ads as $spec) {
            $ad = Ad::updateOrCreate(
                ['ad_account_id' => $account->id, 'name' => $spec['name']],
                ['status' => 'ACTIVE'],
            );

            // Split lifetime totals across 3 days with a mild taper.
            foreach ($dates as $i => $date) {
                $share = [0.40, 0.33, 0.27][$i];
                $impr = (int) round($spec['impressions'] * $share);
                $v3 = (int) round($spec['video_3s'] * $share);
                $tp = (int) round($spec['thruplays'] * $share);
                $spend = round($spec['spend'] * $share, 2);
                $results = round($spec['results'] * $share, 2);

                AdMetric::updateOrCreate(
                    ['ad_id' => $ad->id, 'date' => $date],
                    [
                        'spend' => $spend,
                        'impressions' => $impr,
                        'reach' => (int) round($impr * 0.82),
                        'ctr_all' => round($spec['ctr_link'] * 1.6, 4),
                        'ctr_link' => $spec['ctr_link'],
                        'cpm' => $impr > 0 ? round($spend / $impr * 1000, 4) : null,
                        'cpc' => null,
                        'thruplays' => $tp ?: null,
                        'video_3s' => $v3 ?: null,
                        'results' => $results,
                        'cost_per_result' => $results > 0 ? round($spend / $results, 4) : null,
                        'roas' => $spec['roas'],
                    ],
                );
            }
        }
    }
}
