<?php

namespace Database\Seeders;

use App\Models\Competitor;
use App\Models\CompetitorAd;
use App\Models\Organization;
use App\Support\CurrentOrganization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Demo competitors + ads (MY context) so Brand Spy is usable before Ad Library
 * API access is granted (§7). Clearly demo data; idempotent. days_running is
 * derived from first_seen so the "longest-running" ranking is meaningful.
 */
class DemoCompetitorsSeeder extends Seeder
{
    /** @var array<string, array<int, array{body:string, days:int, active:bool}>> */
    private array $competitors = [
        'Senzu Electronics' => [
            ['body' => 'Raya Mega Sale — up to 60% off home appliances. Free shipping nationwide!', 'days' => 96, 'active' => true],
            ['body' => 'New air fryer bundle. Buy 1 Free 1 this week only.', 'days' => 41, 'active' => true],
            ['body' => 'Merdeka clearance — last units. Grab before it’s gone.', 'days' => 12, 'active' => false],
        ],
        'KongsiFashion' => [
            ['body' => 'Baju Raya 2026 collection is here. Shop the lookbook now.', 'days' => 78, 'active' => true],
            ['body' => 'Real customers, real reviews — see why 10k+ love our tudung.', 'days' => 54, 'active' => true],
            ['body' => 'Flash sale: RM39 tees. Ends midnight.', 'days' => 6, 'active' => true],
        ],
        'MakanBox MY' => [
            ['body' => 'Buka puasa made easy — ready-to-eat sets delivered to your door.', 'days' => 63, 'active' => true],
            ['body' => 'Tired of cooking after work? Try our weekly meal plan.', 'days' => 21, 'active' => true],
        ],
    ];

    public function run(): void
    {
        $org = Organization::firstOrCreate(['slug' => 'stackx'], ['name' => 'STACKx']);
        app(CurrentOrganization::class)->set($org->id);
        $today = Carbon::today();

        foreach ($this->competitors as $name => $ads) {
            $competitor = Competitor::updateOrCreate(
                ['organization_id' => $org->id, 'name' => $name],
                ['meta_page_id' => (string) fake()->numerify('##########'), 'last_synced_at' => $today],
            );

            foreach ($ads as $i => $ad) {
                CompetitorAd::updateOrCreate(
                    ['competitor_id' => $competitor->id, 'ad_library_id' => 'demo_'.Str::slug($name).'_'.$i],
                    [
                        'body' => $ad['body'],
                        'snapshot_url' => 'https://www.facebook.com/ads/library/?id=demo'.$i,
                        'platforms' => ['facebook', 'instagram'],
                        'first_seen' => $today->copy()->subDays($ad['days'])->toDateString(),
                        'last_seen' => $today->toDateString(),
                        'days_running' => $ad['days'],
                        'is_active' => $ad['active'],
                    ],
                );
            }
        }
    }
}
