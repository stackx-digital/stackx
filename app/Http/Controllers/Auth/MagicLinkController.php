<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\MagicLoginLink;
use App\Support\Allowlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Passwordless auth for the STACKx team. Email → signed magic link → session.
 * No passwords, no public registration. The allowlist is enforced both when
 * sending the link and when consuming it.
 */
class MagicLinkController extends Controller
{
    /** Show the login screen. */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /** Email a one-time sign-in link — only to allowlisted addresses. */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = Str::lower($validated['email']);

        // Only mint + send a link for allowlisted emails. We always return the
        // same confirmation so the form can't be used to probe the allowlist.
        if (Allowlist::allows($email)) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => Str::of($email)->before('@')->headline()->value(),
                    'password' => bcrypt(Str::random(40)),
                ],
            );

            $url = URL::temporarySignedRoute(
                'login.verify',
                now()->addMinutes(config('stackx.magic_link_ttl')),
                ['user' => $user->id],
            );

            $user->notify(new MagicLoginLink($url));
        }

        return back()->with(
            'status',
            'If your email is on the STACKx team allowlist, a sign-in link is on its way.',
        );
    }

    /** Consume a signed link and start the session. */
    public function verify(Request $request, User $user): RedirectResponse
    {
        // Signature is validated by the 'signed' middleware. Re-check the
        // allowlist in case the user was removed after the link was issued.
        if (! Allowlist::allows($user->email)) {
            return redirect()->route('not-authorized');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('analytics'));
    }

    /** Sign out. */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
