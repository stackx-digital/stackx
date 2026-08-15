<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_renders(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_signup_creates_an_org_and_user_and_logs_in(): void
    {
        Event::fake();

        $response = $this->post('/register', [
            'name' => 'Aisyah Rahman',
            'company' => 'Nova Agency',
            'email' => 'aisyah@nova.my',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'aisyah@nova.my')->firstOrFail();
        $this->assertNotNull($user->organization_id);

        $org = Organization::findOrFail($user->organization_id);
        $this->assertSame('Nova Agency', $org->name);
        $this->assertSame('nova-agency', $org->slug);

        // A verification email is dispatched (email not yet verified).
        $this->assertNull($user->email_verified_at);
        Event::assertDispatched(Registered::class);
    }

    public function test_company_defaults_to_name_when_omitted(): void
    {
        $this->post('/register', [
            'name' => 'Solo Founder',
            'email' => 'solo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'solo@example.com')->firstOrFail();
        $this->assertSame('Solo Founder', $user->organization->name);
    }

    public function test_each_signup_gets_its_own_isolated_org(): void
    {
        $this->post('/register', [
            'name' => 'One', 'email' => 'one@a.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);
        $this->post('/logout');

        $this->post('/register', [
            'name' => 'Two', 'email' => 'two@b.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $orgs = User::whereIn('email', ['one@a.com', 'two@b.com'])
            ->pluck('organization_id');

        $this->assertCount(2, $orgs->unique());
        $this->assertSame(2, Organization::count());
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', [
            'name' => 'Dup', 'email' => 'taken@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');
    }
}
