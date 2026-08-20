<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The core SaaS guarantee: one tenant can never see another tenant's data.
 * Isolation is enforced by CurrentOrganization (resolved from the signed-in
 * user's organization_id) + the BelongsToOrganization global scope.
 */
class TenancyTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $slug): array
    {
        $org = Organization::create(['name' => ucfirst($slug), 'slug' => $slug]);
        $user = User::factory()->create(['organization_id' => $org->id]);

        app(CurrentOrganization::class)->set($org->id);
        $account = AdAccount::create(['organization_id' => $org->id, 'name' => "{$slug} acc", 'currency' => 'MYR']);
        $ad = Ad::create(['organization_id' => $org->id, 'ad_account_id' => $account->id, 'name' => "{$slug} ad"]);
        app(CurrentOrganization::class)->forget();

        return [$user, $ad];
    }

    public function test_current_org_follows_the_authenticated_user(): void
    {
        [$userA] = $this->tenant('alpha');
        [$userB] = $this->tenant('beta');

        $this->actingAs($userA);
        $this->assertSame($userA->organization_id, app(CurrentOrganization::class)->id());

        app(CurrentOrganization::class)->forget();
        $this->actingAs($userB);
        $this->assertSame($userB->organization_id, app(CurrentOrganization::class)->id());
    }

    public function test_query_scope_hides_other_tenants_ads(): void
    {
        [$userA, $adA] = $this->tenant('alpha');
        [$userB, $adB] = $this->tenant('beta');

        $this->actingAs($userA);
        app(CurrentOrganization::class)->forget();

        $visible = Ad::pluck('id');
        $this->assertTrue($visible->contains($adA->id));
        $this->assertFalse($visible->contains($adB->id));
    }

    public function test_analytics_page_only_counts_the_tenants_own_ads(): void
    {
        [$userA] = $this->tenant('alpha');
        [$userB] = $this->tenant('beta');

        $this->actingAs($userA)
            ->get('/analytics')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('summary.adCount', 1));
    }
}
