<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $org = Organization::create(['name' => 'Acme', 'slug' => 'acme-'.uniqid()]);

        return User::factory()->create(['organization_id' => $org->id]);
    }

    public function test_forgot_password_screen_renders(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

            return true;
        });
    }
}
