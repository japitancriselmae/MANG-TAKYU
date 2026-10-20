<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_manages_roles_and_login_accounts_from_the_employee_list(): void
    {
        $manager = $this->createEmployeeUser('Manager', 'Test', 'Manager', 'manager@example.com');
        $employee = $this->createEmployee('Cashier', 'Jamie', 'Cashier');
        $this->actingAs($manager);

        $response = $this->get(route('employees.index'));

        $response->assertSee('Employees')
            ->assertSee('Role')
            ->assertSee('Login Account')
            ->assertSee('Cashier')
            ->assertSee('Create login')
            ->assertDontSee('User Accounts')
            ->assertDontSee('Roles');
    }

    public function test_manager_can_create_an_employee_login_from_the_employee_list(): void
    {
        $manager = $this->createEmployeeUser('Manager', 'Test', 'Manager', 'manager@example.com');
        $employee = $this->createEmployee('Kitchen Staff', 'Jordan', 'Kitchen');
        $this->actingAs($manager);

        $this->get(route('employees.index'))
            ->assertSee(route('users.create', ['employee_id' => $employee->employee_id]), false);

        $this->get(route('users.create', ['employee_id' => $employee->employee_id]))
            ->assertSeeInOrder([
                'value="'.$employee->employee_id.'"',
                'selected',
            ], false);

        $response = $this->post(route('users.store'), [
            'employee_id' => $employee->employee_id,
            'email' => 'jordan@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('users', [
            'employee_id' => $employee->employee_id,
            'email' => 'jordan@example.com',
        ]);
    }

    private function createEmployeeUser(
        string $roleName,
        string $firstName,
        string $lastName,
        string $email,
    ): User {
        $employee = $this->createEmployee($roleName, $firstName, $lastName);

        return User::create([
            'name' => trim($firstName.' '.$lastName),
            'email' => $email,
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);
    }

    private function createEmployee(string $roleName, string $firstName, string $lastName): Employee
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return Employee::create([
            'role_id' => $role->role_id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'Active',
        ]);
    }
}
