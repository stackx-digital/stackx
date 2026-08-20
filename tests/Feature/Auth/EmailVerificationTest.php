<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function unverified(): User
    {
        $org = Organization::create(['name' => 'Acme', 'slug' => 'acme-'.uniqid()]);

        return User::factory()->unverified()->create(['organization_id' => $org->id]);
    }

    public function test_unverified_user_is_bounced_from_app_to_notice(): void
    {
        $user = $this->unverified();

        $this->actingAs($user)->get('/analytics')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verification_notice_screen_renders(): void
    {
        $this->actingAs($this->unverified())
            ->get('/verify-email')
            ->assertOk();
    }

    public function test_email_can_be_verified_via_signed_link(): void
    {
        Event::fake();
        $user = $this->unverified();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($url)->assertRedirect();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_verified_user_can_reach_the_app(): void
    {
        $org = Organization::create(['name' => 'Acme', 'slug' => 'acme-v']);
        $user = User::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user)->get('/analytics')->assertOk();
    }
}
