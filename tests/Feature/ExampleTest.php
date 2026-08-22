<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root path serves the public marketing home page.
     *
     * This previously asserted a redirect to login, which was true when the
     * root domain was the login-gated EMR. Since the marketing site shipped
     * (B3) the root domain is a public page and the assertion has been failing.
     * A red baseline hides real failures, and the A1 isolation suite is about
     * to depend on that signal being clean.
     */
    public function test_the_root_path_serves_the_marketing_home_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('AfriChart', escape: false);
    }
}
