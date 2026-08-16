<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $orgAttrs = []): User
    {
        $org = Organization::create(array_merge(
            ['name' => 'Acme', 'slug' => 'acme-'.uniqid()],
            $orgAttrs,
        ));

        return User::factory()->create(['organization_id' => $org->id]);
    }

    public function test_welcome_page_renders_with_derived_steps(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->get('/welcome')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Onboarding/Welcome')
                ->where('steps.aiConfigured', false)
                ->where('steps.hasData', false));
    }

    public function test_steps_reflect_real_state(): void
    {
        $user = $this->user();
        app(CurrentOrganization::class)->set($user->organization_id);
        OrganizationSetting::create([
            'organization_id' => $user->organization_id,
            'ai_provider' => 'anthropic',
            'anthropic_api_key' => 'sk-ant-x',
        ]);
        $account = AdAccount::create(['organization_id' => $user->organization_id, 'name' => 'Acc', 'currency' => 'MYR']);
        Ad::create(['organization_id' => $user->organization_id, 'ad_account_id' => $account->id, 'name' => 'Ad']);
        app(CurrentOrganization::class)->forget();

        $this->actingAs($user)
            ->get('/welcome')
            ->assertInertia(fn ($page) => $page
                ->where('steps.aiConfigured', true)
                ->where('steps.hasData', true));
    }

    public function test_complete_stamps_onboarded_at_and_redirects(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post('/welcome/complete')
            ->assertRedirect(route('analytics'));

        $this->assertNotNull($user->organization->fresh()->onboarded_at);
    }

    public function test_onboarding_flag_is_shared_and_flips_after_completion(): void
    {
        $user = $this->user();

        // Before: banner should show (not completed).
        $this->actingAs($user)
            ->get('/analytics')
            ->assertInertia(fn ($page) => $page->where('onboarding.completed', false));

        $this->post('/welcome/complete');

        // After: completed.
        $this->actingAs($user)
            ->get('/analytics')
            ->assertInertia(fn ($page) => $page->where('onboarding.completed', true));
    }

    public function test_completing_twice_keeps_the_original_timestamp(): void
    {
        $user = $this->user(['onboarded_at' => now()->subDay()]);
        $original = $user->organization->onboarded_at;

        $this->actingAs($user)->post('/welcome/complete');

        $this->assertEquals(
            $original->toDateTimeString(),
            $user->organization->fresh()->onboarded_at->toDateTimeString(),
        );
    }

    public function test_email_verification_lands_on_welcome(): void
    {
        $org = Organization::create(['name' => 'New', 'slug' => 'new-'.uniqid()]);
        $user = User::factory()->unverified()->create(['organization_id' => $org->id]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('welcome'));
    }
}
