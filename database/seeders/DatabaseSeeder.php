<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Pre-provision team members from the allowlist. Only full-email entries
     * are seeded (domain globs like "@stackx.my" can't map to a single user;
     * those people are created on first magic-link login instead).
     */
    public function run(): void
    {
        $emails = collect(config('stackx.allowed_emails'))
            ->map(fn ($e) => Str::lower(trim($e)))
            ->reject(fn ($e) => $e === '' || Str::startsWith($e, '@'))
            ->unique();

        foreach ($emails as $email) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => Str::of($email)->before('@')->headline()->value(),
                    'password' => bcrypt(Str::random(40)),
                ],
            );
        }
    }
}
