<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_an_overnight_shift_template(): void
    {
        $user = $this->createManager();

        $response = $this->actingAs($user)->post(route('shift-templates.store'), [
            'name' => 'Night Shift',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'is_overnight' => '1',
            'required_employee_count' => 2,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('shift-templates.index'));
        $this->assertDatabaseHas('shift_templates', [
            'name' => 'Night Shift',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'is_overnight' => true,
            'required_employee_count' => 2,
            'is_active' => true,
        ]);
    }

    public function test_manager_can_update_a_template_and_deactivate_it(): void
    {
        $user = $this->createManager();
        $template = ShiftTemplate::create([
            'name' => 'Morning',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_overnight' => false,
            'required_employee_count' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('shift-templates.update', $template), [
            'name' => 'Morning Team',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_overnight' => '0',
            'required_employee_count' => 3,
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('shift-templates.index'));
        $this->assertDatabaseHas('shift_templates', [
            'shift_template_id' => $template->shift_template_id,
            'name' => 'Morning Team',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_overnight' => false,
            'required_employee_count' => 3,
            'is_active' => false,
        ]);
    }

    public function test_rest_day_template_can_be_viewed_and_saved_without_times(): void
    {
        $user = $this->createManager();
        $template = ShiftTemplate::create([
            'name' => 'Rest Day',
            'start_time' => null,
            'end_time' => null,
            'is_overnight' => false,
            'required_employee_count' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('shift-templates.show', $template))
            ->assertOk()
            ->assertSee('Rest day');

        $response = $this->actingAs($user)->put(route('shift-templates.update', $template), [
            'name' => 'Rest Day',
            'start_time' => '',
            'end_time' => '',
            'is_overnight' => '0',
            'required_employee_count' => 0,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('shift-templates.index'));
        $this->assertDatabaseHas('shift_templates', [
            'shift_template_id' => $template->shift_template_id,
            'start_time' => null,
            'end_time' => null,
            'required_employee_count' => 0,
        ]);
    }

    public function test_manager_can_delete_a_shift_template(): void
    {
        $user = $this->createManager();
        $template = ShiftTemplate::create([
            'name' => 'Morning',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_overnight' => false,
            'required_employee_count' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('shift-templates.destroy', $template));

        $response->assertRedirect(route('shift-templates.index'));
        $this->assertDatabaseMissing('shift_templates', [
            'shift_template_id' => $template->shift_template_id,
        ]);
    }

    private function createManager(): User
    {
        $role = Role::create(['role_name' => 'Manager']);
        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Test',
            'last_name' => 'Manager',
            'status' => 'Active',
        ]);

        return User::create([
            'name' => 'Test Manager',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);
    }
}
