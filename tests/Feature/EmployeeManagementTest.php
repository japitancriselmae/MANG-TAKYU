<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
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
        $createdUser = User::query()
            ->where('email', 'jordan@example.com')
            ->firstOrFail();
        $userActivity = ActivityLog::query()
            ->where('action', 'user.created')
            ->firstOrFail();
        $this->assertSame((string) $createdUser->id, $userActivity->subject_id);
        $this->assertSame([
            'name' => 'Jordan Kitchen',
            'employee_id' => $employee->employee_id,
            'role' => 'Kitchen Staff',
        ], $userActivity->new_values);
    }

    public function test_manager_employee_creation_and_update_record_auditable_values(): void
    {
        $manager = $this->createEmployeeUser('Manager', 'Test', 'Manager', 'manager@example.com');
        $cashierRole = Role::firstOrCreate(['role_name' => 'Cashier']);
        $kitchenStaffRole = Role::firstOrCreate(['role_name' => 'Kitchen Staff']);
        $this->actingAs($manager)
            ->post(route('employees.store'), [
                'role_id' => $cashierRole->role_id,
                'first_name' => 'Jamie',
                'last_name' => 'Cashier',
                'status' => 'Active',
            ])
            ->assertRedirect(route('employees.index'));

        $employee = Employee::query()
            ->where('first_name', 'Jamie')
            ->where('last_name', 'Cashier')
            ->firstOrFail();
        $createdLog = ActivityLog::query()
            ->where('action', 'employee.created')
            ->firstOrFail();
        $this->assertSame($manager->id, $createdLog->user_id);
        $this->assertSame('Jamie', $createdLog->new_values['first_name']);
        $this->assertSame('Cashier', $createdLog->new_values['role']);

        $this->actingAs($manager)
            ->put(route('employees.update', $employee), [
                'role_id' => $kitchenStaffRole->role_id,
                'first_name' => 'Jamie',
                'last_name' => 'Santos',
                'status' => 'Active',
            ])
            ->assertRedirect(route('employees.index'));

        $updatedLog = ActivityLog::query()
            ->where('action', 'employee.updated')
            ->firstOrFail();
        $this->assertSame('Cashier', $updatedLog->old_values['last_name']);
        $this->assertSame('Santos', $updatedLog->new_values['last_name']);
        $this->assertSame('Cashier', $updatedLog->old_values['role']);
        $this->assertSame('Kitchen Staff', $updatedLog->new_values['role']);

        $this->actingAs($manager)
            ->put(route('employees.update', $employee), [
                'role_id' => $kitchenStaffRole->role_id,
                'first_name' => 'Jamie',
                'last_name' => 'Santos',
                'status' => 'Inactive',
            ])
            ->assertRedirect(route('employees.index'));

        $deactivatedLog = ActivityLog::query()
            ->where('action', 'employee.deactivated')
            ->firstOrFail();
        $this->assertSame('Active', $deactivatedLog->old_values['status']);
        $this->assertSame('Inactive', $deactivatedLog->new_values['status']);

        $manager->employee->update(['status' => 'Inactive']);
        $deactivatedLog->refresh();
        $this->assertModelExists($deactivatedLog);
        $this->assertSame($manager->id, $deactivatedLog->user_id);
        $this->assertSame('Test Manager', $deactivatedLog->actor_name);
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
