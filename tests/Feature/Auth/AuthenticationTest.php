<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attrs = []): User
    {
        $org = Organization::create(['name' => 'Acme', 'slug' => 'acme-'.uniqid()]);

        return User::factory()->create(array_merge(['organization_id' => $org->id], $attrs));
    }

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = $this->user();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('analytics'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_authenticate_with_wrong_password(): void
    {
        $user = $this->user();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/analytics')->assertRedirect(route('login'));
    }
}
