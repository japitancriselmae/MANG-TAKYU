<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function createManager(): array
    {
        $role = Role::create([
            'role_name' => 'Manager',
        ]);

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

        return [$user, $employee];
    }

    public function test_manager_can_view_schedules(): void
    {
        [$user, $employee] = $this->createManager();

        $response = $this->actingAs($user)->get('/schedules');

        $response->assertStatus(200);
    }

    public function test_manager_can_create_schedule(): void
    {
        [$user, $employee] = $this->createManager();

        $response = $this->actingAs($user)
            ->post('/schedules', [
                'employee_id' => $employee->employee_id,
                'schedule_date' => '2026-09-18',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'status' => 'Active',
            ]);

        $response->assertRedirect('/schedules');

        $this->assertDatabaseHas('schedules', [
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'Active',
        ]);
    }

    public function test_manager_can_update_schedule(): void
    {
        [$user, $employee] = $this->createManager();

        $schedule = Schedule::create([
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)
            ->put("/schedules/{$schedule->schedule_id}", [
                'employee_id' => $employee->employee_id,
                'schedule_date' => '2026-09-18',
                'start_time' => '09:00',
                'end_time' => '18:00',
                'status' => 'Active',
            ]);

        $response->assertRedirect('/schedules');

        $this->assertDatabaseHas('schedules', [
            'schedule_id' => $schedule->schedule_id,
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]);
    }

    public function test_manager_can_delete_schedule(): void
    {
        [$user, $employee] = $this->createManager();

        $schedule = Schedule::create([
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)
            ->delete("/schedules/{$schedule->schedule_id}");

        $response->assertRedirect('/schedules');

        $this->assertDatabaseMissing('schedules', [
            'schedule_id' => $schedule->schedule_id,
        ]);
    }

    public function test_manager_cannot_delete_schedule_with_attendance(): void
    {
        [$user, $employee] = $this->createManager();

        $schedule = Schedule::create([
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        Attendance::create([
            'employee_id' => $employee->employee_id,
            'schedule_id' => $schedule->schedule_id,
            'date' => '2026-09-18',
            'time_in' => '08:00:00',
            'time_out' => null,
            'status' => 'Present',
            'late_minutes' => 0,
            'undertime_minutes' => 0,
        ]);

        $response = $this->actingAs($user)
            ->delete("/schedules/{$schedule->schedule_id}");

        $response->assertSessionHasErrors('schedule');

        $this->assertDatabaseHas('schedules', [
            'schedule_id' => $schedule->schedule_id,
        ]);
    }

    public function test_schedule_end_time_must_be_after_start_time(): void
    {
        [$user, $employee] = $this->createManager();

        $response = $this->actingAs($user)
            ->post('/schedules', [
                'employee_id' => $employee->employee_id,
                'schedule_date' => '2026-09-18',
                'start_time' => '17:00',
                'end_time' => '08:00',
                'status' => 'Active',
            ]);

        $response->assertSessionHasErrors('end_time');

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_cashier_cannot_manage_schedules(): void
    {
        $role = Role::create([
            'role_name' => 'Cashier',
        ]);

        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Test',
            'last_name' => 'Cashier',
            'status' => 'Active',
        ]);

        $user = User::create([
            'name' => 'Test Cashier',
            'email' => 'cashier@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);

        $response = $this->actingAs($user)
            ->get('/schedules');

        $response->assertStatus(403);
    }

    public function test_manager_cannot_create_overlapping_schedule(): void
    {
        [$user, $employee] = $this->createManager();

        Schedule::create([
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)
            ->post('/schedules', [
                'employee_id' => $employee->employee_id,
                'schedule_date' => '2026-09-18',
                'start_time' => '10:00',
                'end_time' => '15:00',
                'status' => 'Active',
            ]);

        $response->assertSessionHasErrors('schedule');

        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_manager_can_create_non_overlapping_schedule(): void
    {
        [$user, $employee] = $this->createManager();

        Schedule::create([
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)
            ->post('/schedules', [
                'employee_id' => $employee->employee_id,
                'schedule_date' => '2026-09-18',
                'start_time' => '13:00',
                'end_time' => '17:00',
                'status' => 'Active',
            ]);

        $response->assertRedirect('/schedules');

        $this->assertDatabaseCount('schedules', 2);
    }

    public function test_manager_cannot_update_into_overlapping_schedule(): void
    {
        [$user, $employee] = $this->createManager();

        Schedule::create([
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'Active',
        ]);

        $scheduleToUpdate = Schedule::create([
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '13:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)
            ->put("/schedules/{$scheduleToUpdate->schedule_id}", [
                'employee_id' => $employee->employee_id,
                'schedule_date' => '2026-09-18',
                'start_time' => '10:00',
                'end_time' => '14:00',
                'status' => 'Active',
            ]);

        $response->assertSessionHasErrors('schedule');

        $this->assertDatabaseHas('schedules', [
            'schedule_id' => $scheduleToUpdate->schedule_id,
            'start_time' => '13:00:00',
            'end_time' => '17:00:00',
        ]);
    }

    public function test_inactive_schedule_does_not_block_active_schedule(): void
    {
        [$user, $employee] = $this->createManager();

        Schedule::create([
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Inactive',
        ]);

        $response = $this->actingAs($user)
            ->post('/schedules', [
                'employee_id' => $employee->employee_id,
                'schedule_date' => '2026-09-18',
                'start_time' => '10:00',
                'end_time' => '15:00',
                'status' => 'Active',
            ]);

        $response->assertRedirect('/schedules');

        $this->assertDatabaseCount('schedules', 2);
    }
}
