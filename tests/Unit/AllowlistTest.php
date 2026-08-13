<?php

namespace Tests\Unit;

use App\Support\Allowlist;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AllowlistTest extends TestCase
{
    public function test_full_email_match_is_case_insensitive(): void
    {
        Config::set('stackx.allowed_emails', ['alice@stackx.my']);

        $this->assertTrue(Allowlist::allows('alice@stackx.my'));
        $this->assertTrue(Allowlist::allows('ALICE@STACKx.MY'));
        $this->assertFalse(Allowlist::allows('bob@stackx.my'));
    }

    public function test_domain_glob_allows_any_address_at_domain(): void
    {
        Config::set('stackx.allowed_emails', ['@stackx.my']);

        $this->assertTrue(Allowlist::allows('anyone@stackx.my'));
        $this->assertFalse(Allowlist::allows('anyone@gmail.com'));
    }

    public function test_empty_allowlist_fails_closed(): void
    {
        Config::set('stackx.allowed_emails', []);

        $this->assertFalse(Allowlist::allows('alice@stackx.my'));
    }

    public function test_blank_email_is_rejected(): void
    {
        Config::set('stackx.allowed_emails', ['@stackx.my']);

        $this->assertFalse(Allowlist::allows(null));
        $this->assertFalse(Allowlist::allows(''));
    }
}
