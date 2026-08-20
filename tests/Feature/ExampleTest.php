<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** The root route sends guests to the login screen. */
    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }
}
