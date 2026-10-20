<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_link_opens_account_settings_for_each_role(): void
    {
        foreach (['Admin', 'Manager', 'Cashier', 'Kitchen Staff'] as $roleName) {
            $user = $this->createUserForRole($roleName);
            $this->actingAs($user);

            $this->get(route('dashboard'))
                ->assertSee(route('account-settings.edit'), false);

            $this->get(route('account-settings.edit'))
                ->assertSee('Change Password')
                ->assertSee($user->email);
        }
    }

    public function test_user_can_change_password_after_confirming_current_password(): void
    {
        $user = $this->createUserForRole('Cashier');
        $this->actingAs($user);

        $response = $this->put(route('account-settings.update'), [
            'current_password' => 'password123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertRedirect(route('account-settings.edit'));
        $response->assertSessionHas('success', 'Your password has been changed successfully.');
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('new-password-456', $user->fresh()->password));
    }

    public function test_password_cannot_be_changed_with_an_incorrect_current_password(): void
    {
        $user = $this->createUserForRole('Manager');
        $this->actingAs($user);

        $response = $this->from(route('account-settings.edit'))
            ->put(route('account-settings.update'), [
                'current_password' => 'incorrect-password',
                'password' => 'new-password-456',
                'password_confirmation' => 'new-password-456',
            ]);

        $response->assertRedirect(route('account-settings.edit'));
        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public function test_guest_cannot_open_or_update_account_settings(): void
    {
        $this->get(route('account-settings.edit'))->assertRedirect(route('login'));

        $this->put(route('account-settings.update'), [
            'current_password' => 'password123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])->assertRedirect(route('login'));
    }

    private function createUserForRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);
        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Test',
            'last_name' => str_replace(' ', '', $roleName),
            'status' => 'Active',
        ]);

        return User::create([
            'name' => 'Test '.$roleName,
            'email' => strtolower(str_replace(' ', '.', $roleName)).'@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);
    }
}
