<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\Organization;
use App\Models\Report;
use App\Models\User;
use App\Services\Reporting\ReportBuilder;
use App\Services\Scoring\ScoringService;
use App\Support\CurrentOrganization;
use Database\Seeders\DemoAdsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportsTest extends TestCase
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

    private function user(): User
    {
        return User::factory()->create(['email' => 'buyer@stackx.my']);
    }

    private function seedScored(): void
    {
        (new DemoAdsSeeder)->run();
        app(ScoringService::class)->scoreAccount(AdAccount::where('name', 'Demo — Raya Campaign')->firstOrFail());
    }

    private function makeReport(Organization $org): Report
    {
        return Report::create([
            'organization_id' => $org->id,
            'title' => 'Test report',
            'token' => Report::newToken(),
            'payload' => app(ReportBuilder::class)->build(),
        ]);
    }

    public function test_create_report_snapshots_analytics(): void
    {
        $this->org();
        $this->seedScored();

        $this->actingAs($this->user())->post('/reports')->assertRedirect(route('reports'));

        $report = Report::firstOrFail();
        $this->assertArrayHasKey('summary', $report->payload);
        $this->assertNotEmpty($report->payload['winners']);
    }

    public function test_public_report_is_viewable_without_auth(): void
    {
        $org = $this->org();
        $this->seedScored();
        $report = $this->makeReport($org);

        $this->get('/r/'.$report->token)   // no actingAs — guest
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Public')
                ->where('title', 'Test report')
                ->has('payload.summary'));
    }

    public function test_revoked_report_404s(): void
    {
        $org = $this->org();
        $report = $this->makeReport($org);
        $token = $report->token;

        $this->actingAs($this->user())
            ->delete("/reports/{$report->id}")
            ->assertRedirect(route('reports'));

        $this->get('/r/'.$token)->assertNotFound();
    }

    public function test_unknown_token_404s(): void
    {
        $this->get('/r/nope-nope-nope')->assertNotFound();
    }
}
