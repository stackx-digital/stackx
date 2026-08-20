<?php

namespace Tests\Feature;

use App\Models\Competitor;
use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use Database\Seeders\DemoCompetitorsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SpyTest extends TestCase
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

    public function test_index_lists_competitors_with_flag_state(): void
    {
        $this->org();
        (new DemoCompetitorsSeeder)->run();

        $this->actingAs($this->user())
            ->get('/spy')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Spy/Index')
                ->has('competitors', 3)
                ->where('adLibraryEnabled', false)
                ->has('competitors.0.ads'));
    }

    public function test_load_demo_competitors(): void
    {
        $this->org();

        $this->actingAs($this->user())
            ->post('/spy/demo')
            ->assertRedirect(route('spy'));

        $this->assertSame(3, Competitor::count());
    }

    public function test_add_competitor_is_org_scoped(): void
    {
        $org = $this->org();

        $this->actingAs($this->user())
            ->post('/spy/competitors', ['name' => 'New Rival', 'meta_page_id' => '999'])
            ->assertRedirect(route('spy'));

        $this->assertDatabaseHas('competitors', [
            'name' => 'New Rival',
            'organization_id' => $org->id,
        ]);
    }
}
