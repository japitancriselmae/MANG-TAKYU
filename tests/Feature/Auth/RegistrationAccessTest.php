<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_does_not_offer_public_account_creation(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('First-time setup');
        $response->assertDontSee('Need to create the first account?');
    }

    public function test_guests_cannot_open_or_submit_public_registration(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Unauthorized User',
            'email' => 'unauthorized@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }
}
