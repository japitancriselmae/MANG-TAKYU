<?php

namespace Tests\Feature;

use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    /**
     * Guests cannot access the dashboard.
     */
    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    /**
     * Guests can access the login page.
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
