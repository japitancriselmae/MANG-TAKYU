<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_persists_actor_subject_and_activity_values(): void
    {
        $this->travelTo('2026-11-02 12:00:00');
        $actor = User::factory()->create(['name' => 'Test Manager']);
        $subject = Role::create(['role_name' => 'Cashier']);
        $this->actingAs($actor);

        $activityLog = app(ActivityLogger::class)->record(
            action: 'employee.updated',
            module: 'employees',
            description: 'Employee role updated.',
            subject: $subject,
            oldValues: ['role' => 'Cashier'],
            newValues: ['role' => 'Kitchen Staff'],
        );

        $this->assertModelExists($activityLog);
        $this->assertSame($actor->id, $activityLog->user_id);
        $this->assertSame('Test Manager', $activityLog->actor_name);
        $this->assertSame(Role::class, $activityLog->subject_type);
        $this->assertSame((string) $subject->getKey(), $activityLog->subject_id);
        $this->assertSame($subject->getKey(), $activityLog->subject->getKey());
        $this->assertSame(['role' => 'Cashier'], $activityLog->old_values);
        $this->assertSame(['role' => 'Kitchen Staff'], $activityLog->new_values);
        $this->assertSame(ActivityLog::STATUS_SUCCESS, $activityLog->status);
        $this->assertSame('2026-11-02 12:00:00', $activityLog->created_at->format('Y-m-d H:i:s'));
    }

    public function test_record_redacts_sensitive_values_in_nested_snapshots(): void
    {
        $activityLog = app(ActivityLogger::class)->record(
            action: 'user.updated',
            module: 'users',
            description: 'User details updated.',
            oldValues: [
                'password' => 'plaintext-secret',
                'profile' => [
                    'api_token' => 'token-secret',
                    'display_name' => 'Jamie',
                    'email' => 'jamie@example.com',
                    'phone_number' => '+1-555-0100',
                    'home_address' => 'Private Street',
                    'national_id' => '123-45-6789',
                ],
                'password_changed' => true,
                'email_changed' => true,
            ],
        );

        $activityLog->refresh();

        $this->assertSame([
            'password' => '[REDACTED]',
            'profile' => [
                'api_token' => '[REDACTED]',
                'display_name' => 'Jamie',
                'email' => '[REDACTED]',
                'phone_number' => '[REDACTED]',
                'home_address' => '[REDACTED]',
                'national_id' => '[REDACTED]',
            ],
            'password_changed' => true,
            'email_changed' => true,
        ], $activityLog->old_values);
    }

    public function test_actor_deletion_preserves_the_activity_and_actor_snapshot(): void
    {
        $actor = User::factory()->create(['name' => 'Former Manager']);
        $this->actingAs($actor);

        $activityLog = app(ActivityLogger::class)->record(
            action: 'employee.created',
            module: 'employees',
            description: 'Employee created.',
        );

        $actor->delete();
        $activityLog->refresh();

        $this->assertNull($activityLog->user_id);
        $this->assertSame('Former Manager', $activityLog->actor_name);
        $this->assertModelExists($activityLog);
    }

    public function test_activity_log_model_rejects_updates(): void
    {
        $activityLog = app(ActivityLogger::class)->record(
            action: 'employee.created',
            module: 'employees',
            description: 'Employee created.',
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Activity logs are append-only.');

        $activityLog->description = 'Edited description.';
        $activityLog->save();
    }

    public function test_activity_log_model_rejects_deletions(): void
    {
        $activityLog = app(ActivityLogger::class)->record(
            action: 'employee.created',
            module: 'employees',
            description: 'Employee created.',
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Activity logs are append-only.');

        $activityLog->delete();
    }

    public function test_activity_log_query_builder_rejects_mass_updates(): void
    {
        $activityLog = app(ActivityLogger::class)->record(
            action: 'employee.created',
            module: 'employees',
            description: 'Employee created.',
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Activity logs are append-only.');

        ActivityLog::query()
            ->whereKey($activityLog->getKey())
            ->update(['description' => 'Changed through a mass update.']);
    }

    public function test_activity_log_query_builder_rejects_mass_deletions(): void
    {
        $activityLog = app(ActivityLogger::class)->record(
            action: 'employee.created',
            module: 'employees',
            description: 'Employee created.',
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Activity logs are append-only.');

        ActivityLog::query()
            ->whereKey($activityLog->getKey())
            ->delete();
    }

    public function test_record_rejects_unsupported_activity_statuses(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Activity status must be success or failure.');

        app(ActivityLogger::class)->record(
            action: 'login.failed',
            module: 'authentication',
            description: 'Login attempt failed.',
            status: 'pending',
        );
    }
}
