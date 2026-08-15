<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\AdMetric;
use App\Models\Alert;
use App\Models\Organization;
use App\Models\User;
use App\Services\Alerts\AlertDetector;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('stackx.allowed_emails', ['@stackx.my']);
    }

    private function org(): Organization
    {
        $org = Organization::firstOrCreate(['slug' => 'stackx'], ['name' => 'STACKx']);
        app(CurrentOrganization::class)->set($org->id);

        return $org;
    }

    private function ad(AdAccount $account, string $name): Ad
    {
        return Ad::create(['organization_id' => $account->organization_id, 'ad_account_id' => $account->id, 'name' => $name]);
    }

    private function metric(Ad $ad, int $daysAgo, array $attrs): void
    {
        AdMetric::create(['ad_id' => $ad->id, 'date' => Carbon::today()->subDays($daysAgo)->toDateString(), ...$attrs]);
    }

    private function seedAlerts(): Organization
    {
        $org = $this->org();
        $account = AdAccount::create(['organization_id' => $org->id, 'name' => 'Acc', 'currency' => 'MYR']);

        // Fatiguing ad: CTR halves while frequency doubles.
        $tired = $this->ad($account, 'Tired');
        $this->metric($tired, 4, ['ctr_link' => 2.0, 'impressions' => 1000, 'reach' => 1000]);
        $this->metric($tired, 3, ['ctr_link' => 2.0, 'impressions' => 1000, 'reach' => 1000]);
        $this->metric($tired, 2, ['ctr_link' => 1.0, 'impressions' => 2000, 'reach' => 1000]);
        $this->metric($tired, 1, ['ctr_link' => 1.0, 'impressions' => 2000, 'reach' => 1000]);

        // A middling ad to lower the blended ROAS.
        $avg = $this->ad($account, 'Average');
        foreach ([4, 3, 2, 1] as $d) {
            $this->metric($avg, $d, ['roas' => 2.0, 'spend' => 100, 'impressions' => 500, 'reach' => 500]);
        }

        // Strong, non-declining ROAS → scale opportunity.
        $winner = $this->ad($account, 'Winner');
        $this->metric($winner, 4, ['roas' => 5.5, 'spend' => 100, 'ctr_link' => 1.5, 'impressions' => 500, 'reach' => 500]);
        $this->metric($winner, 3, ['roas' => 5.5, 'spend' => 100, 'ctr_link' => 1.5, 'impressions' => 500, 'reach' => 500]);
        $this->metric($winner, 2, ['roas' => 6.0, 'spend' => 100, 'ctr_link' => 1.5, 'impressions' => 500, 'reach' => 500]);
        $this->metric($winner, 1, ['roas' => 6.0, 'spend' => 100, 'ctr_link' => 1.5, 'impressions' => 500, 'reach' => 500]);

        return $org;
    }

    public function test_detects_fatigue_and_scale(): void
    {
        $this->seedAlerts();

        app(AlertDetector::class)->detect();

        $this->assertDatabaseHas('alerts', ['type' => 'fatigue', 'severity' => 'high']);
        $this->assertDatabaseHas('alerts', ['type' => 'scale']);
        $this->assertTrue(Alert::whereHas('ad', fn ($q) => $q->where('name', 'Winner'))->where('type', 'scale')->exists());
    }

    public function test_detection_is_idempotent(): void
    {
        $this->seedAlerts();

        app(AlertDetector::class)->detect();
        $first = Alert::count();
        app(AlertDetector::class)->detect();

        $this->assertSame($first, Alert::count()); // deduped per (ad, type)
    }

    public function test_route_detect_and_resolve(): void
    {
        $this->seedAlerts();
        $user = User::factory()->create(['email' => 'buyer@stackx.my']);

        $this->actingAs($user)->post('/alerts/detect')->assertRedirect(route('alerts'));
        $alert = Alert::firstOrFail();

        $this->actingAs($user)->post("/alerts/{$alert->id}/resolve")->assertRedirect(route('alerts'));
        $this->assertNotNull($alert->fresh()->resolved_at);
    }
}
