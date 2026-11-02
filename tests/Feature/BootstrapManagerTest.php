<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BootstrapManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_credentials_create_an_active_manager_account(): void
    {
        config()->set('deployment.initial_manager', [
            'email' => 'manager@example.com',
            'password' => 'a-strong-password-123',
            'first_name' => 'Test',
            'last_name' => 'Manager',
        ]);

        $this->artisan('mang-takyu:bootstrap-manager')
            ->expectsOutputToContain('Initial Manager account created.')
            ->assertSuccessful();

        $user = User::query()->with('employee.role')->sole();

        $this->assertSame('manager@example.com', $user->email);
        $this->assertSame('Manager', $user->employee->role->role_name);
        $this->assertSame('Active', $user->employee->status);
        $this->assertTrue(Hash::check('a-strong-password-123', $user->password));
    }

    public function test_missing_credentials_skip_manager_creation(): void
    {
        config()->set('deployment.initial_manager', [
            'email' => null,
            'password' => null,
            'first_name' => null,
            'last_name' => null,
        ]);

        $this->artisan('mang-takyu:bootstrap-manager')
            ->expectsOutputToContain('Initial Manager credentials are not configured')
            ->assertSuccessful();

        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_credentials_do_not_create_partial_records(): void
    {
        config()->set('deployment.initial_manager', [
            'email' => 'manager@example.com',
            'password' => 'weak',
            'first_name' => 'Test',
            'last_name' => 'Manager',
        ]);

        $this->artisan('mang-takyu:bootstrap-manager')
            ->expectsOutputToContain('The password field must be at least 16 characters.')
            ->assertFailed();

        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_existing_manager_account_is_not_modified_or_duplicated(): void
    {
        $role = Role::create(['role_name' => 'Manager']);
        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Existing',
            'last_name' => 'Manager',
            'status' => 'Active',
        ]);
        $user = User::create([
            'name' => 'Existing Manager',
            'email' => 'existing@example.com',
            'password' => 'existing-password',
            'employee_id' => $employee->employee_id,
        ]);
        $originalPassword = $user->password;

        config()->set('deployment.initial_manager', [
            'email' => 'replacement@example.com',
            'password' => 'another-strong-password',
            'first_name' => 'Replacement',
            'last_name' => 'Manager',
        ]);

        $this->artisan('mang-takyu:bootstrap-manager')
            ->expectsOutputToContain('A Manager account already exists')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($originalPassword, $user->fresh()->password);
    }
}
