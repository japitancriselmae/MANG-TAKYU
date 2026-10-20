<?php

namespace Tests\Feature\Console\Commands;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_admin_account_with_a_confirmed_hidden_password(): void
    {
        $password = 'very-secure-password';

        $this->artisan('mang-takyu:create-admin', ['--email' => 'admin@example.com'])
            ->expectsQuestion('Enter an admin password (at least 12 characters)', $password)
            ->expectsQuestion('Confirm the password', $password)
            ->expectsOutput('Admin account created.')
            ->assertExitCode(0);

        $user = User::with('employee.role')->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame('System Admin', $user->name);
        $this->assertSame('Admin', $user->employee->role->role_name);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertDatabaseHas('employees', [
            'employee_id' => $user->employee_id,
            'first_name' => 'System',
            'last_name' => 'Admin',
        ]);
    }

    public function test_rejects_an_existing_email_without_creating_an_employee(): void
    {
        $role = Role::create(['role_name' => 'Admin']);
        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Existing',
            'last_name' => 'Admin',
            'status' => 'Active',
        ]);
        User::create([
            'name' => 'Existing Admin',
            'email' => 'admin@example.com',
            'password' => 'existing-password',
            'employee_id' => $employee->employee_id,
        ]);
        $employeeCount = Employee::count();

        $this->artisan('mang-takyu:create-admin', ['--email' => 'admin@example.com'])
            ->expectsOutput('A user with this email already exists: admin@example.com')
            ->assertExitCode(1);

        $this->assertSame($employeeCount, Employee::count());
    }
}
