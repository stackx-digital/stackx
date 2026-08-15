<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Allowlist;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Prints a one-time magic-link sign-in URL for an allowlisted email, so the
 * team can get in before SMTP is configured. Run it from Laravel Cloud's
 * Commands tab, then open the printed URL. The email must be on the allowlist.
 */
class LoginLinkCommand extends Command
{
    protected $signature = 'stackx:login-link {email?}';

    protected $description = 'Generate a one-time sign-in link for an allowlisted email (no email delivery needed).';

    public function handle(): int
    {
        $email = Str::lower(trim(
            (string) ($this->argument('email') ?: 'stackxdigital@gmail.com'),
        ));

        if (! Allowlist::allows($email)) {
            $this->error("{$email} is not on STACKX_ALLOWED_EMAILS — add it first, then redeploy.");

            return self::FAILURE;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => Str::of($email)->before('@')->headline()->value(),
                'password' => bcrypt(Str::random(40)),
            ],
        );

        $url = URL::temporarySignedRoute(
            'login.verify',
            now()->addMinutes(60),
            ['user' => $user->id],
        );

        $this->newLine();
        $this->info('Open this link within 60 minutes to sign in:');
        $this->line($url);
        $this->newLine();
        $this->comment('If the host looks wrong, set APP_URL to your real domain and re-run.');

        return self::SUCCESS;
    }
}
