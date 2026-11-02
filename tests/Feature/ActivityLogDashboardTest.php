<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ActivityLogDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_sees_activity_metrics_and_the_recent_activity_table(): void
    {
        $manager = $this->createEmployeeUser('Manager');
        $this->actingAs($manager);
        $activityLogger = app(ActivityLogger::class);

        $this->travelTo(Carbon::parse('2026-11-02 12:00:00'));
        $activityLogger->record(
            action: 'employee.updated',
            module: 'employees',
            description: 'Employee profile updated.',
            oldValues: ['private_note' => 'private audit snapshot'],
        );
        $activityLogger->record(
            action: 'product.updated',
            module: 'products',
            description: 'Product details updated.',
        );
        $activityLogger->record(
            action: 'login.failed',
            module: 'authentication',
            description: 'Login attempt rejected.',
            status: ActivityLog::STATUS_FAILURE,
        );

        $this->travelTo(Carbon::parse('2026-10-31 16:00:00'));
        $activityLogger->record(
            action: 'schedule.approved',
            module: 'schedules',
            description: 'Weekly schedule approved.',
        );

        $this->travelTo(Carbon::parse('2026-11-02 13:00:00'));
        $response = $this->get(route('activity-logs.index'));

        $response->assertViewHas('activityStats', [
            'today' => 3,
            'thisWeek' => 3,
            'successful' => 3,
            'failed' => 1,
        ]);
        $response->assertViewHas('activities', function ($activities): bool {
            return $activities->total() === 4
                && $activities->first()->action === 'login.failed';
        });
        $response->assertSee('Activity Logs')
            ->assertSee('Recent system activities')
            ->assertSee('Test Manager')
            ->assertSee('Login attempt rejected.')
            ->assertSee('Authentication')
            ->assertSee('Failure')
            ->assertDontSee('private audit snapshot');
    }

    public function test_activity_filters_match_user_module_action_status_and_date_range(): void
    {
        $manager = $this->createEmployeeUser('Manager');
        $this->actingAs($manager);
        $activityLogger = app(ActivityLogger::class);
        $this->travelTo(Carbon::parse('2026-11-02 12:00:00'));

        $activityLogger->record(
            action: 'product.updated',
            module: 'products',
            description: 'Matching product update.',
            actorName: 'Jamie Manager',
        );
        $activityLogger->record(
            action: 'product.created',
            module: 'products',
            description: 'Different action.',
            actorName: 'Jamie Manager',
        );
        $activityLogger->record(
            action: 'product.updated',
            module: 'products',
            description: 'Failed product update.',
            status: ActivityLog::STATUS_FAILURE,
            actorName: 'Jamie Manager',
        );
        $activityLogger->record(
            action: 'product.updated',
            module: 'employees',
            description: 'Different module.',
            actorName: 'Jamie Manager',
        );
        $activityLogger->record(
            action: 'product.updated',
            module: 'products',
            description: 'Different user.',
            actorName: 'Morgan Manager',
        );

        $this->travelTo(Carbon::parse('2026-11-01 12:00:00'));
        $activityLogger->record(
            action: 'product.updated',
            module: 'products',
            description: 'Outside date range.',
            actorName: 'Jamie Manager',
        );

        $this->travelTo(Carbon::parse('2026-11-02 15:00:00'));
        $response = $this->get(route('activity-logs.index', [
            'search' => 'Jamie',
            'module' => 'products',
            'action' => 'product.updated',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-02',
            'status' => 'success',
            'sort' => 'oldest',
        ]));

        $response->assertViewHas('activities', function ($activities): bool {
            return $activities->total() === 1
                && $activities->first()->description === 'Matching product update.';
        });
        $response->assertSee('Matching product update.')
            ->assertDontSee('Different action.')
            ->assertDontSee('Failed product update.')
            ->assertDontSee('Different module.')
            ->assertDontSee('Different user.')
            ->assertDontSee('Outside date range.');
    }

    public function test_activity_filter_rejects_invalid_status_sort_and_date_range(): void
    {
        $manager = $this->createEmployeeUser('Manager');

        $this->actingAs($manager)
            ->get(route('activity-logs.index', [
                'status' => 'pending',
                'sort' => 'random',
                'start_date' => '2026-11-02',
                'end_date' => '2026-11-01',
                'search' => str_repeat('a', 101),
            ]))
            ->assertSessionHasErrors(['status', 'sort', 'end_date', 'search']);
    }

    public function test_activity_summaries_show_daily_weekly_and_frequent_action_totals(): void
    {
        $manager = $this->createEmployeeUser('Manager');
        $this->travelTo(Carbon::parse('2026-11-02 12:00:00'));

        $this->createActivity([
            'action' => 'product.updated',
            'module' => 'products',
            'description' => 'First product update.',
            'created_at' => Carbon::parse('2026-11-02 09:00:00'),
        ]);
        $this->createActivity([
            'action' => 'product.updated',
            'module' => 'products',
            'description' => 'Second product update.',
            'created_at' => Carbon::parse('2026-11-02 10:00:00'),
        ]);
        $this->createActivity([
            'action' => 'schedule.approved',
            'module' => 'schedules',
            'description' => 'Schedule approved last week.',
            'created_at' => Carbon::parse('2026-10-30 10:00:00'),
        ]);

        $response = $this->actingAs($manager)->get(route('activity-logs.index'));

        $response->assertViewHas('dailyActivitySummaries', function (array $summaries): bool {
            $this->assertCount(7, $summaries);
            $this->assertSame([0, 0, 0, 1, 0, 0, 2], array_column($summaries, 'total'));

            return true;
        });
        $response->assertViewHas('weeklyActivitySummaries', function (array $summaries): bool {
            return array_column($summaries, 'total') === [0, 0, 1, 2];
        });
        $response->assertViewHas('frequentActions', function ($actions): bool {
            return $actions->count() === 2
                && $actions->first()->action === 'product.updated'
                && (int) $actions->first()->total === 2;
        });
        $response->assertSee('Daily activity')
            ->assertSee('Weekly activity')
            ->assertSee('Frequently performed actions');
    }

    public function test_csv_export_applies_filters_and_excludes_sensitive_context(): void
    {
        $manager = $this->createEmployeeUser('Manager');
        $this->travelTo(Carbon::parse('2026-11-02 12:00:00'));
        $this->createActivity([
            'actor_name' => '=SUM(1,1)',
            'action' => 'product.updated',
            'module' => 'products',
            'description' => '=HYPERLINK("https://example.invalid","Open")',
            'old_values' => ['private_snapshot' => 'do-not-export'],
            'ip_address' => '192.0.2.25',
            'user_agent' => 'private browser context',
            'created_at' => Carbon::parse('2026-11-02 11:00:00'),
        ]);
        $this->createActivity([
            'action' => 'product.deleted',
            'module' => 'products',
            'description' => 'Failed deletion.',
            'status' => ActivityLog::STATUS_FAILURE,
        ]);
        $this->createActivity([
            'action' => 'employee.updated',
            'module' => 'employees',
            'description' => 'Different module.',
        ]);

        $response = $this->actingAs($manager)->get(route('activity-logs.export', [
            'module' => 'products',
            'status' => ActivityLog::STATUS_SUCCESS,
        ]));

        $response->assertDownload('activity-logs-20261102-120000.csv');
        $rows = array_map(
            static fn (string $row): array => str_getcsv($row, ',', '"', ''),
            explode("\n", trim($response->streamedContent())),
        );

        $this->assertSame(
            ['User', 'Action', 'Module', 'Description', 'Status', 'Date and Time'],
            $rows[0],
        );
        $this->assertCount(2, $rows);
        $this->assertSame("'=SUM(1,1)", $rows[1][0]);
        $this->assertSame("'=HYPERLINK(\"https://example.invalid\",\"Open\")", $rows[1][3]);
        $this->assertStringNotContainsString('do-not-export', $response->streamedContent());
        $this->assertStringNotContainsString('192.0.2.25', $response->streamedContent());
        $this->assertStringNotContainsString('private browser context', $response->streamedContent());
    }

    public function test_cashier_cannot_export_activity_logs(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');

        $this->actingAs($cashier)
            ->get(route('activity-logs.export'))
            ->assertForbidden();
    }

    public function test_inactive_manager_cannot_view_activity_logs(): void
    {
        $manager = $this->createEmployeeUser('Manager');
        $manager->employee->update(['status' => 'Inactive']);

        $this->actingAs($manager)
            ->get(route('activity-logs.index'))
            ->assertForbidden();
    }

    public function test_inactive_manager_cannot_view_activity_details(): void
    {
        $manager = $this->createEmployeeUser('Manager');
        $manager->employee->update(['status' => 'Inactive']);
        $activity = $this->createActivity([
            'action' => 'employee.updated',
            'module' => 'employees',
            'description' => 'Employee details updated.',
        ]);

        $this->actingAs($manager)
            ->get(route('activity-logs.show', $activity))
            ->assertForbidden();
    }

    public function test_activity_log_policy_does_not_allow_creating_or_modifying_records(): void
    {
        $manager = $this->createEmployeeUser('Manager');
        $activity = $this->createActivity([
            'action' => 'employee.updated',
            'module' => 'employees',
            'description' => 'Employee details updated.',
        ]);
        $gate = Gate::forUser($manager);

        $this->assertFalse($gate->allows('create', ActivityLog::class));
        $this->assertFalse($gate->allows('update', $activity));
        $this->assertFalse($gate->allows('delete', $activity));
        $this->assertFalse($gate->allows('forceDelete', $activity));
    }

    public function test_oldest_sort_paginates_matching_activities_and_preserves_filters(): void
    {
        $manager = $this->createEmployeeUser('Manager');
        $this->actingAs($manager);
        $activityLogger = app(ActivityLogger::class);

        for ($number = 1; $number <= 17; $number++) {
            $this->travelTo(Carbon::parse('2026-11-02 08:00:00')->addMinutes($number));
            $activityLogger->record(
                action: 'product.updated',
                module: 'products',
                description: 'Activity '.$number,
            );
        }

        $this->travelTo(Carbon::parse('2026-11-02 12:00:00'));
        $response = $this->get(route('activity-logs.index', [
            'module' => 'products',
            'sort' => 'oldest',
        ]));

        $response->assertViewHas('activities', function ($activities): bool {
            return $activities->total() === 17
                && $activities->count() === 15
                && $activities->first()->description === 'Activity 1'
                && $activities->hasPages();
        });
        $response->assertSee('module=products', false)
            ->assertSee('sort=oldest', false);

        $secondPage = $this->get(route('activity-logs.index', [
            'module' => 'products',
            'sort' => 'oldest',
            'page' => 2,
        ]));

        $secondPage->assertViewHas('activities', function ($activities): bool {
            return $activities->total() === 17
                && $activities->count() === 2
                && $activities->first()->description === 'Activity 16';
        });
    }

    public function test_manager_can_view_activity_details_and_snapshots(): void
    {
        $manager = $this->createEmployeeUser('Manager');
        $relatedRole = Role::create(['role_name' => 'Cashier']);
        $activity = $this->createActivity([
            'user_id' => $manager->id,
            'actor_name' => $manager->name,
            'action' => 'role.updated',
            'module' => 'users',
            'description' => 'User role changed.',
            'subject_type' => Role::class,
            'subject_id' => (string) $relatedRole->role_id,
            'old_values' => ['role' => 'Cashier'],
            'new_values' => ['role' => 'Kitchen Staff'],
            'ip_address' => '192.0.2.10',
            'user_agent' => 'ActivityLogDashboardTest browser',
            'created_at' => Carbon::parse('2026-11-02 10:15:00'),
        ]);

        $response = $this->actingAs($manager)
            ->get(route('activity-logs.show', $activity));

        $response->assertSee('User role changed.')
            ->assertSee('Test Manager')
            ->assertSee('role.updated')
            ->assertSee('Users')
            ->assertSee('Cashier')
            ->assertSee('Kitchen Staff')
            ->assertSee('192.0.2.10')
            ->assertSee('ActivityLogDashboardTest browser')
            ->assertSee('Role #'.$relatedRole->role_id);
    }

    public function test_admin_can_access_activity_logs(): void
    {
        $admin = $this->createEmployeeUser('Admin');

        $this->actingAs($admin)
            ->get(route('activity-logs.index'))
            ->assertSee('Recent system activities');
    }

    public function test_cashier_receives_forbidden_response_when_opening_activity_logs(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');

        $this->actingAs($cashier)
            ->get(route('activity-logs.index'))
            ->assertForbidden();
    }

    public function test_cashier_receives_forbidden_response_when_opening_activity_details(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $activity = $this->createActivity([
            'actor_name' => 'Test Manager',
            'action' => 'employee.updated',
            'module' => 'employees',
            'description' => 'Employee details updated.',
            'status' => ActivityLog::STATUS_SUCCESS,
            'created_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->get(route('activity-logs.show', $activity))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_when_opening_activity_logs(): void
    {
        $this->get(route('activity-logs.index'))
            ->assertRedirect(route('login'));
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createActivity(array $attributes): ActivityLog
    {
        $activity = new ActivityLog;
        $activity->forceFill(array_merge([
            'user_id' => null,
            'actor_name' => 'Test Actor',
            'action' => 'activity.test',
            'module' => 'testing',
            'description' => 'Test activity.',
            'status' => ActivityLog::STATUS_SUCCESS,
            'created_at' => now(),
        ], $attributes));
        $activity->save();

        return $activity;
    }
}
