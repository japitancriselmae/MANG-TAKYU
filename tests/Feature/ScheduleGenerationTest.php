<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_generation_excludes_only_the_dates_covered_by_approved_leave(): void
    {
        $this->freezeTime();

        $manager = $this->createEmployeeWithUser('Manager', 'manager@example.com');
        $employeeOnLeave = $this->createEmployee('Kitchen Staff');
        $weekStart = now()->startOfWeek();
        $leaveDate = $weekStart->copy()->addDay();

        Leave::create([
            'employee_id' => $employeeOnLeave->employee_id,
            'start_date' => $leaveDate->toDateString(),
            'end_date' => $leaveDate->toDateString(),
            'leave_type' => 'Personal',
            'status' => 'approved',
        ]);
        ShiftTemplate::create([
            'name' => 'Morning',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_overnight' => false,
            'required_employee_count' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($manager)->post(route('schedules.generate'), [
            'week_start_date' => $weekStart->toDateString(),
        ]);

        $this->assertDatabaseMissing('schedules', [
            'employee_id' => $employeeOnLeave->employee_id,
            'schedule_date' => $leaveDate->toDateString(),
        ]);
        $this->assertDatabaseHas('schedules', [
            'employee_id' => $employeeOnLeave->employee_id,
            'schedule_date' => $weekStart->copy()->addDays(2)->toDateString(),
        ]);
    }

    public function test_manager_can_generate_approve_and_publish_a_weekly_schedule(): void
    {
        $this->freezeTime();

        $manager = $this->createEmployeeWithUser('Manager', 'manager@example.com');
        $admin = $this->createEmployee('Admin');
        $staff = $this->createEmployee('Kitchen Staff');
        ShiftTemplate::create([
            'name' => 'Morning',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_overnight' => false,
            'required_employee_count' => 1,
            'is_active' => true,
        ]);
        $weekStart = now()->startOfWeek();

        $response = $this->actingAs($manager)->post(route('schedules.generate'), [
            'week_start_date' => $weekStart->toDateString(),
        ]);

        $response->assertRedirect(route('schedules.index', [
            'week_start_date' => $weekStart->toDateString(),
        ]));
        $this->assertDatabaseCount('schedules', 7);
        $this->assertDatabaseMissing('schedules', [
            'employee_id' => $manager->employee_id,
        ]);
        $this->assertDatabaseMissing('schedules', [
            'employee_id' => $admin->employee_id,
        ]);
        $this->assertDatabaseHas('schedules', [
            'employee_id' => $staff->employee_id,
            'schedule_date' => $weekStart->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'schedule_status' => Schedule::STATUS_DRAFT,
        ]);

        $schedule = Schedule::query()->firstOrFail();
        $this->actingAs($manager)
            ->post(route('schedules.approve', $schedule))
            ->assertRedirect(route('schedules.index', [
                'week_start_date' => $weekStart->toDateString(),
            ]));

        $this->assertDatabaseMissing('schedules', [
            'schedule_status' => Schedule::STATUS_DRAFT,
        ]);
        $this->assertDatabaseHas('schedules', [
            'schedule_status' => Schedule::STATUS_APPROVED,
            'approved_by' => $manager->employee_id,
        ]);

        $this->actingAs($manager)
            ->post(route('schedules.publish', $schedule))
            ->assertRedirect(route('schedules.index', [
                'week_start_date' => $weekStart->toDateString(),
            ]));

        $this->assertDatabaseMissing('schedules', [
            'schedule_status' => Schedule::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('schedules', [
            'schedule_status' => Schedule::STATUS_PUBLISHED,
        ]);
    }

    public function test_schedule_generation_approval_and_publication_are_logged(): void
    {
        $this->freezeTime();

        $manager = $this->createEmployeeWithUser('Manager', 'manager@example.com');
        $this->createEmployee('Kitchen Staff');
        ShiftTemplate::create([
            'name' => 'Morning',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_overnight' => false,
            'required_employee_count' => 1,
            'is_active' => true,
        ]);
        $weekStart = now()->startOfWeek();

        $this->actingAs($manager)
            ->post(route('schedules.generate'), [
                'week_start_date' => $weekStart->toDateString(),
            ])
            ->assertRedirect(route('schedules.index', [
                'week_start_date' => $weekStart->toDateString(),
            ]));

        $schedule = Schedule::query()->firstOrFail();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'schedule.generated',
            'module' => 'scheduling',
            'user_id' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->post(route('schedules.approve', $schedule))
            ->assertRedirect(route('schedules.index', [
                'week_start_date' => $weekStart->toDateString(),
            ]));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'schedule.approved',
            'user_id' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->post(route('schedules.publish', $schedule))
            ->assertRedirect(route('schedules.index', [
                'week_start_date' => $weekStart->toDateString(),
            ]));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'schedule.published',
            'user_id' => $manager->id,
        ]);
    }

    private function createEmployeeWithUser(string $roleName, string $email): User
    {
        $employee = $this->createEmployee($roleName);

        return User::create([
            'name' => $roleName,
            'email' => $email,
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);
    }

    private function createEmployee(string $roleName): Employee
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Test',
            'last_name' => $roleName,
            'status' => 'Active',
        ]);
    }
}
