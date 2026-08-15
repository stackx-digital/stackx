<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public self-serve signup (SaaS). Each registration provisions a brand-new
 * organization (tenant) and its first user — solo model, 1 user = 1 org. All
 * of that account's data is scoped to the org via CurrentOrganization, so a
 * fresh signup starts completely empty and isolated.
 */
class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $workspace = ($validated['company'] ?? '') ?: $validated['name'];

        $user = DB::transaction(function () use ($validated, $workspace) {
            $organization = Organization::create([
                'name' => $workspace,
                'slug' => $this->uniqueSlug($workspace),
            ]);

            return User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'organization_id' => $organization->id,
            ]);
        });

        // Point scoping at the new tenant for the rest of this request, fire the
        // Registered event (sends the verification email), and start a session.
        app(CurrentOrganization::class)->set($user->organization_id);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    /** A slug that's unique across orgs, derived from the workspace name. */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(6));
        }

        return $slug;
    }
}
