<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
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

    public function test_failed_login_records_a_failure_without_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'known-user@example.com',
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $activityLog = ActivityLog::query()
            ->where('action', 'auth.login_failed')
            ->firstOrFail();
        $this->assertSame(ActivityLog::STATUS_FAILURE, $activityLog->status);
        $this->assertSame($user->id, $activityLog->user_id);
        $this->assertNull($activityLog->old_values);
        $this->assertNull($activityLog->new_values);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_successful_login_and_logout_are_recorded_without_credentials(): void
    {
        $role = Role::create(['role_name' => 'Manager']);
        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Test',
            'last_name' => 'Manager',
            'status' => 'Active',
        ]);
        $user = User::create([
            'name' => 'Test Manager',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'auth.login',
            'user_id' => $user->id,
            'status' => ActivityLog::STATUS_SUCCESS,
        ]);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'auth.logout',
            'user_id' => $user->id,
            'status' => ActivityLog::STATUS_SUCCESS,
        ]);
        $this->assertStringNotContainsString(
            'password123',
            ActivityLog::query()->get()->toJson()
        );
    }
}
