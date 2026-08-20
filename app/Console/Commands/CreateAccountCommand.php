<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Provision a ready-to-use account (org + verified user) from the CLI, so the
 * first tenant can sign in before SMTP is configured. Run it from Laravel
 * Cloud's Commands tab. The email is marked verified immediately, so no
 * verification email is needed.
 */
class CreateAccountCommand extends Command
{
    protected $signature = 'stackx:account
        {email : The login email}
        {--name= : Display name (defaults to the email handle)}
        {--company= : Organization/workspace name (defaults to the name)}
        {--password= : Password (a random one is generated and printed if omitted)}';

    protected $description = 'Create an organization and a verified user for email+password sign-in.';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("“{$email}” is not a valid email address.");

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("A user with {$email} already exists.");

            return self::FAILURE;
        }

        $name = trim((string) $this->option('name')) ?: Str::of($email)->before('@')->headline()->value();
        $company = trim((string) $this->option('company')) ?: $name;
        $password = (string) ($this->option('password') ?: Str::password(16));

        $user = DB::transaction(function () use ($name, $company, $email, $password) {
            $org = Organization::create([
                'name' => $company,
                'slug' => $this->uniqueSlug($company),
            ]);

            app(CurrentOrganization::class)->set($org->id);

            return User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'organization_id' => $org->id,
                'email_verified_at' => now(),
            ]);
        });

        $this->newLine();
        $this->info('Account ready — sign in at /login:');
        $this->line("  Email:    {$user->email}");
        $this->line("  Password: {$password}");
        $this->line("  Org:      {$user->organization->name} (#{$user->organization_id})");
        $this->newLine();
        $this->comment('Change the password after first sign-in.');

        return self::SUCCESS;
    }

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
