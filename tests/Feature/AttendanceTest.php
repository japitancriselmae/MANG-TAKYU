<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_access_attendance(): void
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
            'email' => 'testmanager@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendances');

        $response->assertStatus(200);
    }

    public function test_employee_can_clock_in(): void
    {
        Carbon::setTestNow('2026-09-18 08:00:00');

        $role = Role::create([
            'role_name' => 'Manager',
        ]);

        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'status' => 'Active',
        ]);

        $user = User::create([
            'name' => 'Test Employee',
            'email' => 'employee@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);

        $schedule = Schedule::create([
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)
            ->post('/attendances');

        $response->assertRedirect('/attendances');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->employee_id,
            'schedule_id' => $schedule->schedule_id,
            'date' => '2026-09-18 00:00:00',
            'status' => 'Present',
            'late_minutes' => 0,
            'undertime_minutes' => 0,
        ]);

        Carbon::setTestNow();
    }

    public function test_employee_cannot_clock_in_twice(): void
    {
        Carbon::setTestNow('2026-09-18 08:00:00');

        $role = Role::create([
            'role_name' => 'Manager',
        ]);

        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'status' => 'Active',
        ]);

        $user = User::create([
            'name' => 'Test Employee',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);

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
            ->post('/attendances');

        $response->assertSessionHasErrors('attendance');

        $this->assertDatabaseCount('attendances', 1);

        Carbon::setTestNow();
    }

    public function test_employee_can_clock_out(): void
    {
        Carbon::setTestNow('2026-09-18 16:30:00');

        $role = Role::create([
            'role_name' => 'Manager',
        ]);

        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'status' => 'Active',
        ]);

        $user = User::create([
            'name' => 'Test Employee',
            'email' => 'clockout@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);

        $schedule = Schedule::create([
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        $attendance = Attendance::create([
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
            ->post(
                "/attendances/{$attendance->attendance_id}/clock-out"
            );

        $response->assertRedirect('/attendances');

        $this->assertDatabaseHas('attendances', [
            'attendance_id' => $attendance->attendance_id,
            'time_out' => '16:30:00',
            'undertime_minutes' => 30,
        ]);

        Carbon::setTestNow();
    }

    public function test_late_minutes_are_calculated(): void
    {
        Carbon::setTestNow('2026-09-18 08:15:00');

        $role = Role::create([
            'role_name' => 'Manager',
        ]);

        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Late',
            'last_name' => 'Employee',
            'status' => 'Active',
        ]);

        $user = User::create([
            'name' => 'Late Employee',
            'email' => 'late@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);

        $schedule = Schedule::create([
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)
            ->post('/attendances');

        $response->assertRedirect('/attendances');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->employee_id,
            'schedule_id' => $schedule->schedule_id,
            'late_minutes' => 15,
        ]);

        Carbon::setTestNow();
    }

    public function test_early_clock_out_calculates_undertime(): void
    {
        Carbon::setTestNow('2026-09-18 08:00:00');

        $role = Role::create([
            'role_name' => 'Manager',
        ]);

        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Early',
            'last_name' => 'Employee',
            'status' => 'Active',
        ]);

        $user = User::create([
            'name' => 'Early Employee',
            'email' => 'early@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);

        $schedule = Schedule::create([
            'employee_id' => $employee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        $attendance = Attendance::create([
            'employee_id' => $employee->employee_id,
            'schedule_id' => $schedule->schedule_id,
            'date' => '2026-09-18',
            'time_in' => '08:00:00',
            'time_out' => null,
            'status' => 'Present',
            'late_minutes' => 0,
            'undertime_minutes' => 0,
        ]);

        Carbon::setTestNow('2026-09-18 16:30:00');

        $response = $this->actingAs($user)
            ->post(
                "/attendances/{$attendance->attendance_id}/clock-out"
            );

        $response->assertRedirect('/attendances');

        $this->assertDatabaseHas('attendances', [
            'attendance_id' => $attendance->attendance_id,
            'undertime_minutes' => 30,
        ]);

        Carbon::setTestNow();
    }

    public function test_employee_cannot_access_another_employees_attendance(): void
    {
        $role = Role::create([
            'role_name' => 'Cashier',
        ]);

        $employeeOne = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Employee',
            'last_name' => 'One',
            'status' => 'Active',
        ]);

        $employeeTwo = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Employee',
            'last_name' => 'Two',
            'status' => 'Active',
        ]);

        $userOne = User::create([
            'name' => 'Employee One',
            'email' => 'employeeone@example.com',
            'password' => 'password123',
            'employee_id' => $employeeOne->employee_id,
        ]);

        $schedule = Schedule::create([
            'employee_id' => $employeeTwo->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        $attendance = Attendance::create([
            'employee_id' => $employeeTwo->employee_id,
            'schedule_id' => $schedule->schedule_id,
            'date' => '2026-09-18',
            'time_in' => '08:00:00',
            'time_out' => null,
            'status' => 'Present',
            'late_minutes' => 0,
            'undertime_minutes' => 0,
        ]);

        $response = $this->actingAs($userOne)
            ->get(
                "/attendances/{$attendance->attendance_id}"
            );

        $response->assertStatus(403);
    }

    public function test_manager_can_detect_employee_as_absent(): void
    {
        Carbon::setTestNow('2026-09-18 09:00:00');

        $role = Role::create([
            'role_name' => 'Manager',
        ]);

        $managerEmployee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'System',
            'last_name' => 'Manager',
            'status' => 'Active',
        ]);

        $scheduledEmployee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Absent',
            'last_name' => 'Employee',
            'status' => 'Active',
        ]);

        $manager = User::create([
            'name' => 'System Manager',
            'email' => 'absence-manager@example.com',
            'password' => 'password123',
            'employee_id' => $managerEmployee->employee_id,
        ]);

        Schedule::create([
            'employee_id' => $scheduledEmployee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($manager)
            ->get('/attendances');

        $response->assertStatus(200);

        $response->assertViewHas('absentEmployees');

        $absentEmployees = $response->viewData('absentEmployees');

        $this->assertCount(1, $absentEmployees);
        $this->assertTrue(
            $absentEmployees->first()->employee_id
                === $scheduledEmployee->employee_id
        );

        Carbon::setTestNow();
    }

    public function test_employee_who_clocked_in_is_not_detected_as_absent(): void
    {
        Carbon::setTestNow('2026-09-18 09:00:00');

        $role = Role::create([
            'role_name' => 'Manager',
        ]);

        $managerEmployee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'System',
            'last_name' => 'Manager',
            'status' => 'Active',
        ]);

        $scheduledEmployee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Present',
            'last_name' => 'Employee',
            'status' => 'Active',
        ]);

        $manager = User::create([
            'name' => 'System Manager',
            'email' => 'present-manager@example.com',
            'password' => 'password123',
            'employee_id' => $managerEmployee->employee_id,
        ]);

        Schedule::create([
            'employee_id' => $scheduledEmployee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        Attendance::create([
            'employee_id' => $scheduledEmployee->employee_id,
            'schedule_id' => Schedule::where(
                'employee_id',
                $scheduledEmployee->employee_id
            )->first()->schedule_id,
            'date' => '2026-09-18',
            'time_in' => '08:05:00',
            'time_out' => null,
            'status' => 'Present',
            'late_minutes' => 5,
            'undertime_minutes' => 0,
        ]);

        $response = $this->actingAs($manager)
            ->get('/attendances');

        $response->assertStatus(200);

        $absentEmployees = $response->viewData('absentEmployees');

        $this->assertCount(0, $absentEmployees);

        Carbon::setTestNow();
    }

    public function test_employee_before_schedule_start_is_not_detected_as_absent(): void
    {
        Carbon::setTestNow('2026-09-18 07:30:00');

        $role = Role::create([
            'role_name' => 'Manager',
        ]);

        $managerEmployee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'System',
            'last_name' => 'Manager',
            'status' => 'Active',
        ]);

        $scheduledEmployee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Future',
            'last_name' => 'Employee',
            'status' => 'Active',
        ]);

        $manager = User::create([
            'name' => 'System Manager',
            'email' => 'future-manager@example.com',
            'password' => 'password123',
            'employee_id' => $managerEmployee->employee_id,
        ]);

        Schedule::create([
            'employee_id' => $scheduledEmployee->employee_id,
            'schedule_date' => '2026-09-18',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($manager)
            ->get('/attendances');

        $response->assertStatus(200);

        $absentEmployees = $response->viewData('absentEmployees');

        $this->assertCount(0, $absentEmployees);

        Carbon::setTestNow();
    }

    public function test_employee_without_schedule_is_not_detected_as_absent(): void
    {
        Carbon::setTestNow('2026-09-18 09:00:00');

        $role = Role::create([
            'role_name' => 'Manager',
        ]);

        $managerEmployee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'System',
            'last_name' => 'Manager',
            'status' => 'Active',
        ]);

        Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'No',
            'last_name' => 'Schedule',
            'status' => 'Active',
        ]);

        $manager = User::create([
            'name' => 'System Manager',
            'email' => 'noschedule-manager@example.com',
            'password' => 'password123',
            'employee_id' => $managerEmployee->employee_id,
        ]);

        $response = $this->actingAs($manager)
            ->get('/attendances');

        $response->assertStatus(200);

        $absentEmployees = $response->viewData('absentEmployees');

        $this->assertCount(0, $absentEmployees);

        Carbon::setTestNow();
    }
}
