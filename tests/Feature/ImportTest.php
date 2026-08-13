<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\AdMetric;
use App\Models\Organization;
use App\Models\User;
use App\Services\Ingest\AdMetricsImporter;
use App\Services\Ingest\MetaCsvParser;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    private string $csv = <<<'CSV'
    Ad name,Amount spent (MYR),Impressions,CTR (link click-through rate),ThruPlays,3-second video plays,Purchase ROAS,Reporting starts
    Raya Sale,"1,234.50",100000,1.25%,5000,20000,4.2,2026-08-01
    Ramadan Bundle,RM 800,50000,0.90%,1000,8000,2.1,2026-08-01
    CSV;

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

    private function actingAsTeam(): User
    {
        return User::factory()->create(['email' => 'buyer@stackx.my']);
    }

    public function test_importer_upserts_ads_and_metrics_idempotently(): void
    {
        $org = $this->org();
        $account = AdAccount::create(['organization_id' => $org->id, 'name' => 'Test', 'currency' => 'MYR']);
        $parsed = (new MetaCsvParser)->parseString($this->csv);
        $importer = new AdMetricsImporter;

        $importer->import($account, $parsed);
        $importer->import($account, $parsed); // re-run

        $this->assertSame(2, Ad::count());
        $this->assertSame(2, AdMetric::count());

        $metric = AdMetric::whereHas('ad', fn ($q) => $q->where('name', 'Raya Sale'))->first();
        $this->assertEquals(1234.50, (float) $metric->spend);
        $this->assertEquals(1.25, (float) $metric->ctr_link);
        $this->assertSame(20000, $metric->video_3s);
        $this->assertEquals('2026-08-01', $metric->date->toDateString());
    }

    public function test_missing_metric_is_stored_as_null(): void
    {
        $org = $this->org();
        $account = AdAccount::create(['organization_id' => $org->id, 'name' => 'Test', 'currency' => 'MYR']);

        // No ROAS column at all.
        $csv = "Ad name,Amount spent (MYR),Impressions\nRaya,1000,50000";
        $parsed = (new MetaCsvParser)->parseString($csv);
        (new AdMetricsImporter)->import($account, $parsed);

        $metric = AdMetric::first();
        $this->assertNull($metric->roas);
        $this->assertEquals(1000, (float) $metric->spend);
    }

    public function test_preview_endpoint_returns_mapping(): void
    {
        $this->org();
        $user = $this->actingAsTeam();

        $file = UploadedFile::fake()->createWithContent('meta.csv', $this->csv);

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->post('/analytics/import/preview', ['file' => $file]);

        $response->assertOk();
        $this->assertSame('Analytics/Import', $response->json('component'));
        $this->assertSame(2, $response->json('props.preview.rowCount'));
        $this->assertNotEmpty($response->json('props.importToken'));
    }

    public function test_full_import_flow_via_routes(): void
    {
        $this->org();
        $user = $this->actingAsTeam();

        $file = UploadedFile::fake()->createWithContent('meta.csv', $this->csv);
        $preview = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->post('/analytics/import/preview', ['file' => $file]);

        $token = $preview->json('props.importToken');

        $this->actingAs($user)
            ->post('/analytics/import', ['import_token' => $token])
            ->assertRedirect(route('analytics'))
            ->assertSessionHas('status');

        $this->assertSame(2, Ad::count());
        $this->assertDatabaseHas('ad_accounts', ['name' => 'CSV Import']);
    }

    public function test_demo_data_loads(): void
    {
        $this->org();
        $user = $this->actingAsTeam();

        $this->actingAs($user)->post('/analytics/demo')
            ->assertRedirect(route('analytics'));

        $this->assertSame(7, Ad::count());
        $this->assertTrue(AdMetric::count() >= 7);
        $this->assertDatabaseHas('ad_accounts', ['name' => 'Demo — Raya Campaign']);
    }
}
