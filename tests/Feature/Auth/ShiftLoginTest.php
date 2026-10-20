<?php

namespace Tests\Feature\Auth;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShiftLoginTest extends TestCase
{
    use RefreshDatabase;

    public static function shiftBasedRoles(): array
    {
        return [
            'cashier' => ['Cashier'],
            'kitchen staff' => ['Kitchen Staff'],
        ];
    }

    #[DataProvider('shiftBasedRoles')]
    public function test_staff_login_records_attendance_and_shows_proof_reminder(string $roleName): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 07:30:00'));
        $user = $this->createEmployeeUser($roleName);
        $schedule = $this->createSchedule($user, '2026-10-13', '08:00:00', '17:00:00');

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee_id,
            'schedule_id' => $schedule->schedule_id,
            'date' => '2026-10-13 00:00:00',
            'time_in' => '07:30:00',
            'status' => 'Present',
            'late_minutes' => 0,
        ]);

        $this->get('/')
            ->assertSee('Attendance proof required')
            ->assertSee('Send Proof Photo')
            ->assertSee('Remind me next page')
            ->assertSee('aria-modal="true"', false)
            ->assertSee('capture="user"', false);
        $this->get('/attendances')->assertSee('Attendance proof required');
    }

    public function test_staff_login_is_rejected_after_the_scheduled_shift_ends(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 17:00:00'));
        $user = $this->createEmployeeUser('Cashier');
        $this->createSchedule($user, '2026-10-13', '08:00:00', '17:00:00');

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'Your shift has ended. You cannot log in after your scheduled end time.',
        ]);
        $this->assertGuest();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_staff_login_is_rejected_without_an_active_shift_today(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 09:00:00'));
        $user = $this->createEmployeeUser('Kitchen Staff');

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'No active shift is scheduled for today. Please contact your manager.',
        ]);
        $this->assertGuest();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_late_staff_login_records_late_minutes_automatically(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:15:00'));
        $user = $this->createEmployeeUser('Cashier');
        $this->createSchedule($user, '2026-10-13', '08:00:00', '17:00:00');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee_id,
            'time_in' => '08:15:00',
            'status' => 'Late',
            'late_minutes' => 15,
        ]);
    }

    public function test_manager_can_log_in_without_a_shift_and_sees_logout_confirmation(): void
    {
        $manager = $this->createEmployeeUser('Manager');

        $response = $this->post('/login', [
            'email' => $manager->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($manager);
        $this->assertDatabaseCount('attendances', 0);
        $this->get('/')
            ->assertSee('Are you sure you want to log out?')
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('Yes, log out')
            ->assertSee('No, stay logged in')
            ->assertDontSee('onsubmit="return confirm', false);
    }

    public function test_manager_logout_does_not_record_staff_attendance(): void
    {
        $manager = $this->createEmployeeUser('Manager');
        $this->post('/login', [
            'email' => $manager->email,
            'password' => 'password123',
        ])->assertRedirect('/');

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_uploading_attendance_proof_clears_the_global_modal(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 09:00:00'));
        Storage::fake('public');
        $user = $this->createEmployeeUser('Cashier');
        $this->createSchedule($user, '2026-10-13', '08:00:00', '17:00:00');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        $attendance = Attendance::query()
            ->where('employee_id', $user->employee_id)
            ->whereDate('date', '2026-10-13')
            ->firstOrFail();

        $response = $this->post(route('attendances.upload-proof', $attendance), [
            'proof_image' => UploadedFile::fake()->image('station.png'),
        ]);

        $response->assertRedirect('/attendances');
        $attendance->refresh();
        $this->assertNotNull($attendance->proof_image);
        Storage::disk('public')->assertExists($attendance->proof_image);
        $this->get('/')->assertDontSee('Attendance proof required');
    }

    public function test_attendance_page_shows_only_the_log_and_keeps_the_proof_reminder_modal(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 09:00:00'));
        $user = $this->createEmployeeUser('Cashier');
        $this->createSchedule($user, '2026-10-13', '08:00:00', '17:00:00');
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect('/');

        $response = $this->get('/attendances');

        $response->assertSee('My Attendance Log')
            ->assertSee('Attendance proof required')
            ->assertDontSee("Today's Schedule")
            ->assertDontSee('Clock In Now')
            ->assertDontSee('Clock Out Now')
            ->assertDontSee('Proof of Attendance')
            ->assertDontSee('Not Clocked In');
    }

    public function test_non_image_file_is_rejected_as_attendance_proof(): void
    {
        Storage::fake('public');
        $user = $this->createEmployeeUser('Cashier');
        $schedule = $this->createSchedule($user, '2026-10-13', '08:00:00', '17:00:00');
        $attendance = Attendance::create([
            'employee_id' => $user->employee_id,
            'schedule_id' => $schedule->schedule_id,
            'date' => '2026-10-13',
            'time_in' => '08:00:00',
            'status' => 'Present',
            'late_minutes' => 0,
            'undertime_minutes' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('attendances.upload-proof', $attendance), [
            'proof_image' => UploadedFile::fake()->create('proof.txt', 1, 'text/plain'),
        ]);

        $response->assertSessionHasErrors('proof_image');
        $this->assertDatabaseHas('attendances', [
            'attendance_id' => $attendance->attendance_id,
            'proof_image' => null,
        ]);
        Storage::disk('public')->assertDirectoryEmpty('attendance_proofs');
    }

    public function test_relogging_during_a_shift_does_not_create_duplicate_attendance(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 09:00:00'));
        $user = $this->createEmployeeUser('Cashier');
        $this->createSchedule($user, '2026-10-13', '08:00:00', '17:00:00');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect('/');
        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect('/');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee_id,
            'time_in' => '09:00:00',
            'time_out' => '09:00:00',
        ]);
    }

    #[DataProvider('shiftBasedRoles')]
    public function test_staff_logout_records_time_out_and_undertime_minutes(string $roleName): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 09:00:00'));
        $user = $this->createEmployeeUser($roleName);
        $this->createSchedule($user, '2026-10-13', '08:00:00', '17:00:00');
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect('/');
        $this->travelTo(Carbon::parse('2026-10-13 15:30:00'));

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee_id,
            'time_in' => '09:00:00',
            'time_out' => '15:30:00',
            'undertime_minutes' => 90,
        ]);
    }

    public function test_staff_logout_records_the_local_manila_time(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 09:00:00', 'Asia/Manila'));
        $user = $this->createEmployeeUser('Cashier');
        $this->createSchedule($user, '2026-10-13', '08:00:00', '17:00:00');
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect('/');
        $this->travelTo(Carbon::parse('2026-10-13 12:47:00', 'Asia/Manila'));

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee_id,
            'time_out' => '12:47:00',
        ]);
    }

    #[DataProvider('shiftBasedRoles')]
    public function test_staff_logout_after_shift_end_records_zero_undertime(string $roleName): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 09:00:00'));
        $user = $this->createEmployeeUser($roleName);
        $this->createSchedule($user, '2026-10-13', '08:00:00', '17:00:00');
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect('/');
        $this->travelTo(Carbon::parse('2026-10-13 17:30:00'));

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee_id,
            'time_out' => '17:30:00',
            'undertime_minutes' => 0,
        ]);
    }

    public function test_staff_logout_does_not_overwrite_an_existing_clock_out(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 09:00:00'));
        $user = $this->createEmployeeUser('Cashier');
        $this->createSchedule($user, '2026-10-13', '08:00:00', '17:00:00');
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect('/');
        $attendance = Attendance::query()
            ->where('employee_id', $user->employee_id)
            ->whereDate('date', '2026-10-13')
            ->firstOrFail();
        $attendance->update([
            'time_out' => '10:00:00',
            'undertime_minutes' => 420,
        ]);
        $this->travelTo(Carbon::parse('2026-10-13 16:30:00'));

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('attendances', [
            'attendance_id' => $attendance->attendance_id,
            'time_out' => '10:00:00',
            'undertime_minutes' => 420,
        ]);
    }

    public function test_staff_cannot_clock_in_manually_after_the_shift_ends(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 17:01:00'));
        $user = $this->createEmployeeUser('Cashier');
        $this->createSchedule($user, '2026-10-13', '08:00:00', '17:00:00');

        $response = $this->actingAs($user)->post('/attendances');

        $response->assertSessionHasErrors([
            'attendance' => 'You do not have an active shift that has not ended today.',
        ]);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_remember_me_checkbox_submits_valid_boolean(): void
    {
        $user = $this->createEmployeeUser('Manager');

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
            'remember' => '1',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    private function createEmployeeUser(string $roleName): User
    {
        $role = Role::create(['role_name' => $roleName]);
        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Test',
            'last_name' => $roleName,
            'status' => 'Active',
        ]);

        return User::create([
            'name' => 'Test '.$roleName,
            'email' => strtolower(str_replace(' ', '.', $roleName)).'@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);
    }

    private function createSchedule(User $user, string $date, string $start, string $end): Schedule
    {
        return Schedule::create([
            'employee_id' => $user->employee_id,
            'schedule_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'Active',
        ]);
    }
}
