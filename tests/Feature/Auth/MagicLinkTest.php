<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\MagicLoginLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('stackx.allowed_emails', ['@stackx.my']);
    }

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_allowlisted_email_receives_link_and_user_is_created(): void
    {
        Notification::fake();

        $this->post('/login', ['email' => 'buyer@stackx.my'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $user = User::where('email', 'buyer@stackx.my')->first();
        $this->assertNotNull($user);
        Notification::assertSentTo($user, MagicLoginLink::class);
    }

    public function test_non_allowlisted_email_creates_no_user_and_sends_nothing(): void
    {
        Notification::fake();

        $this->post('/login', ['email' => 'outsider@gmail.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['email' => 'outsider@gmail.com']);
        Notification::assertNothingSent();
    }

    public function test_signed_link_logs_the_user_in(): void
    {
        $user = User::factory()->create(['email' => 'buyer@stackx.my']);

        $url = URL::temporarySignedRoute(
            'login.verify',
            now()->addMinutes(15),
            ['user' => $user->id],
        );

        $this->get($url)->assertRedirect(route('analytics'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_unsigned_verify_link_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'buyer@stackx.my']);

        $this->get("/login/{$user->id}/verify")->assertForbidden();
        $this->assertGuest();
    }

    public function test_authenticated_off_allowlist_user_is_bounced(): void
    {
        $user = User::factory()->create(['email' => 'ex@stackx.my']);
        Config::set('stackx.allowed_emails', ['@other.com']);

        $this->actingAs($user)->get('/analytics')
            ->assertRedirect(route('not-authorized'));
    }

    public function test_allowlisted_user_can_reach_analytics(): void
    {
        $user = User::factory()->create(['email' => 'buyer@stackx.my']);

        $this->actingAs($user)->get('/analytics')->assertOk();
    }
}
