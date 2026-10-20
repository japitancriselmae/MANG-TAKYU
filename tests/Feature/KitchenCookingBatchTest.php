<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CookingBatch;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\CookingBatchNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KitchenCookingBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_can_start_a_quantity_tracked_chicken_batch_without_changing_order_status(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 16:00:00'));
        $cashier = $this->createUser('Cashier');
        $kitchenStaff = $this->createUser('Kitchen Staff');
        $order = $this->createPendingOrder($cashier);
        CookingBatch::create([
            'employee_id' => $kitchenStaff->employee_id,
            'food_type' => 'rice',
            'quantity' => 8,
            'duration_minutes' => 20,
            'started_at' => now(),
        ]);

        $this->actingAs($kitchenStaff)
            ->get(route('kitchen.index'))
            ->assertSee('Cooking Batches')
            ->assertSee('Chicken batch quantity')
            ->assertDontSee('rice', false)
            ->assertDontSee('Order #'.$order->order_id)
            ->assertDontSee('Active Orders')
            ->assertDontSee('Waiting for cashier confirmation.')
            ->assertDontSee('8 rice portions')
            ->assertDontSee('Rice Timer')
            ->assertDontSee('Chicken Timer');

        $this->post(route('kitchen.batches.store'), [
            'food_type' => 'chicken',
            'quantity' => 25,
            'duration_minutes' => 12,
        ])->assertRedirect(route('kitchen.index'));

        $this->assertDatabaseHas('cooking_batches', [
            'employee_id' => $kitchenStaff->employee_id,
            'food_type' => 'chicken',
            'quantity' => 25,
            'duration_minutes' => 12,
            'started_at' => '2026-10-13 16:00:00',
        ]);
        $this->get(route('kitchen.index'))
            ->assertSee('25 pcs chicken')
            ->assertSee('data-duration-minutes="12"', false);
        $this->assertSame('Pending', $order->fresh()->status);

        $this->actingAs($cashier)
            ->get(route('kitchen-batch-notifications.index'))
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath(
                'notifications.0.message',
                'Fresh chicken batch (25 pcs) is being cooked. 1 fresh-chicken order is waiting (#'.$order->order_id.').'
            );
    }

    public function test_kitchen_confirms_a_finished_chicken_batch_and_cashier_gets_ready_notification(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 17:00:00'));
        $cashier = $this->createUser('Cashier');
        $kitchenStaff = $this->createUser('Kitchen Staff');
        $order = $this->createPendingOrder($cashier);
        $this->actingAs($kitchenStaff);

        $this->post(route('kitchen.batches.store'), [
            'food_type' => 'chicken',
            'quantity' => 25,
            'duration_minutes' => 1,
        ])->assertRedirect(route('kitchen.index'));
        $batch = CookingBatch::query()->firstOrFail();

        $this->travel(1)->minutes();
        $this->post(route('kitchen.batches.ready', $batch))
            ->assertRedirect(route('kitchen.index'))
            ->assertSessionHas('success', 'Chicken batch confirmed ready.');

        $this->assertDatabaseHas('cooking_batches', [
            'id' => $batch->id,
            'status' => 'ready',
            'ready_at' => '2026-10-13 17:01:00',
        ]);
        $this->assertSame('Pending', $order->fresh()->status);
        $this->get(route('kitchen.index'))
            ->assertSee('Ready for service')
            ->assertDontSee('Confirm Batch Ready');

        $this->actingAs($cashier)
            ->get(route('kitchen-batch-notifications.index'))
            ->assertJsonPath('unread_count', 2)
            ->assertJsonFragment([
                'message' => 'Fresh chicken batch (25 pcs) is ready for service. 1 fresh-chicken order is waiting (#'.$order->order_id.').',
                'status' => 'ready',
            ]);
        $this->get(route('orders.index'))
            ->assertSee('Kitchen Updates')
            ->assertSee('is ready for service');
        $this->get(route('orders.index', ['filter' => 'fresh-chicken']))
            ->assertSee('#'.$order->order_id)
            ->assertDontSee('Fresh Chicken');

        $notification = $cashier->unreadNotifications()
            ->where('type', CookingBatchNotification::class)
            ->get()
            ->first(fn ($notification): bool => $notification->data['status'] === 'ready');
        $this->assertNotNull($notification);

        $otherCashier = $this->createUser('Cashier');
        $this->actingAs($otherCashier)
            ->post(route('kitchen-batch-notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);

        $this->actingAs($cashier)
            ->post(route('kitchen-batch-notifications.read', $notification->id))
            ->assertNoContent();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }

    public function test_kitchen_cannot_confirm_a_chicken_batch_before_its_timer_finishes(): void
    {
        $cashier = $this->createUser('Cashier');
        $kitchenStaff = $this->createUser('Kitchen Staff');
        $this->createPendingOrder($cashier);
        $this->actingAs($kitchenStaff);

        $this->post(route('kitchen.batches.store'), [
            'food_type' => 'chicken',
            'quantity' => 10,
            'duration_minutes' => 5,
        ])->assertRedirect(route('kitchen.index'));

        $batch = CookingBatch::query()->firstOrFail();
        $this->from(route('kitchen.index'))
            ->post(route('kitchen.batches.ready', $batch))
            ->assertRedirect(route('kitchen.index'))
            ->assertSessionHasErrors('batch');

        $this->assertDatabaseHas('cooking_batches', [
            'id' => $batch->id,
            'status' => 'cooking',
            'ready_at' => null,
        ]);
    }

    public function test_each_cooking_batch_keeps_its_own_quantity_and_duration(): void
    {
        $kitchenStaff = $this->createUser('Kitchen Staff');
        $this->actingAs($kitchenStaff);

        $this->post(route('kitchen.batches.store'), [
            'food_type' => 'chicken',
            'quantity' => 25,
            'duration_minutes' => 12,
        ])->assertRedirect(route('kitchen.index'));
        $this->post(route('kitchen.batches.store'), [
            'food_type' => 'chicken',
            'quantity' => 10,
            'duration_minutes' => 8,
        ])->assertRedirect(route('kitchen.index'));

        $this->assertDatabaseCount('cooking_batches', 2);
        $this->assertDatabaseHas('cooking_batches', [
            'food_type' => 'chicken',
            'quantity' => 25,
            'duration_minutes' => 12,
        ]);
        $this->assertDatabaseHas('cooking_batches', [
            'food_type' => 'chicken',
            'quantity' => 10,
            'duration_minutes' => 8,
        ]);
    }

    #[DataProvider('invalidCookingBatchInputs')]
    public function test_kitchen_cannot_start_a_batch_with_invalid_input(
        string $field,
        array $payload
    ): void {
        $kitchenStaff = $this->createUser('Kitchen Staff');

        $this->actingAs($kitchenStaff)
            ->from(route('kitchen.index'))
            ->post(route('kitchen.batches.store'), $payload)
            ->assertRedirect(route('kitchen.index'))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('cooking_batches', 0);
    }

    public static function invalidCookingBatchInputs(): array
    {
        return [
            'unsupported food type' => ['food_type', [
                'food_type' => 'beef',
                'quantity' => 1,
                'duration_minutes' => 1,
            ]],
            'quantity below minimum' => ['quantity', [
                'food_type' => 'chicken',
                'quantity' => 0,
                'duration_minutes' => 1,
            ]],
            'quantity above maximum' => ['quantity', [
                'food_type' => 'chicken',
                'quantity' => 1000001,
                'duration_minutes' => 1,
            ]],
            'duration below minimum' => ['duration_minutes', [
                'food_type' => 'chicken',
                'quantity' => 1,
                'duration_minutes' => 0,
            ]],
            'duration above maximum' => ['duration_minutes', [
                'food_type' => 'chicken',
                'quantity' => 1,
                'duration_minutes' => 1441,
            ]],
        ];
    }

    public function test_cashier_cannot_start_a_cooking_batch(): void
    {
        $cashier = $this->createUser('Cashier');

        $this->actingAs($cashier)
            ->post(route('kitchen.batches.store'), [
                'food_type' => 'chicken',
                'quantity' => 10,
                'duration_minutes' => 10,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('cooking_batches', 0);
    }

    public function test_manager_can_start_a_cooking_batch(): void
    {
        $manager = $this->createUser('Manager');

        $this->actingAs($manager)
            ->post(route('kitchen.batches.store'), [
                'food_type' => 'chicken',
                'quantity' => 5,
                'duration_minutes' => 25,
            ])
            ->assertRedirect(route('kitchen.index'));

        $this->assertDatabaseHas('cooking_batches', [
            'employee_id' => $manager->employee_id,
            'food_type' => 'chicken',
            'quantity' => 5,
            'duration_minutes' => 25,
        ]);
    }

    public function test_kitchen_queue_is_reset_when_kitchen_staff_logs_out_with_unfinished_batch(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 16:00:00'));
        $kitchenStaff = $this->createUser('Kitchen Staff');

        $schedule = Schedule::create([
            'employee_id' => $kitchenStaff->employee_id,
            'schedule_date' => '2026-10-13',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);
        Attendance::create([
            'employee_id' => $kitchenStaff->employee_id,
            'schedule_id' => $schedule->schedule_id,
            'date' => '2026-10-13',
            'time_in' => '09:00:00',
            'time_out' => null,
            'status' => 'Present',
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'proof_image' => null,
        ]);

        $this->actingAs($kitchenStaff)
            ->post(route('kitchen.batches.store'), [
                'food_type' => 'chicken',
                'quantity' => 25,
                'duration_minutes' => 60,
            ])->assertRedirect(route('kitchen.index'));

        $this->assertDatabaseHas('cooking_batches', [
            'employee_id' => $kitchenStaff->employee_id,
            'status' => 'cooking',
        ]);

        $this->actingAs($kitchenStaff)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('cooking_batches', [
            'employee_id' => $kitchenStaff->employee_id,
            'status' => 'cooking',
        ]);
    }

    public function test_kitchen_queue_is_reset_when_kitchen_staff_clocks_out_with_unfinished_batch(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 16:00:00'));
        $kitchenStaff = $this->createUser('Kitchen Staff');

        $schedule = Schedule::create([
            'employee_id' => $kitchenStaff->employee_id,
            'schedule_date' => '2026-10-13',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);
        $attendance = Attendance::create([
            'employee_id' => $kitchenStaff->employee_id,
            'schedule_id' => $schedule->schedule_id,
            'date' => '2026-10-13',
            'time_in' => '09:00:00',
            'time_out' => null,
            'status' => 'Present',
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'proof_image' => null,
        ]);

        $this->actingAs($kitchenStaff)
            ->post(route('kitchen.batches.store'), [
                'food_type' => 'chicken',
                'quantity' => 25,
                'duration_minutes' => 60,
            ])->assertRedirect(route('kitchen.index'));

        $this->assertDatabaseHas('cooking_batches', [
            'employee_id' => $kitchenStaff->employee_id,
            'status' => 'cooking',
        ]);

        $this->actingAs($kitchenStaff)
            ->post(route('attendances.clock-out', $attendance))
            ->assertRedirect(route('attendances.index'));

        $this->assertDatabaseMissing('cooking_batches', [
            'employee_id' => $kitchenStaff->employee_id,
            'status' => 'cooking',
        ]);
    }

    public function test_kitchen_queue_keeps_ready_batch_when_shift_ends(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 16:00:00'));
        $kitchenStaff = $this->createUser('Kitchen Staff');

        $schedule = Schedule::create([
            'employee_id' => $kitchenStaff->employee_id,
            'schedule_date' => '2026-10-13',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);
        Attendance::create([
            'employee_id' => $kitchenStaff->employee_id,
            'schedule_id' => $schedule->schedule_id,
            'date' => '2026-10-13',
            'time_in' => '09:00:00',
            'time_out' => null,
            'status' => 'Present',
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'proof_image' => null,
        ]);

        $this->actingAs($kitchenStaff)
            ->post(route('kitchen.batches.store'), [
                'food_type' => 'chicken',
                'quantity' => 25,
                'duration_minutes' => 1,
            ])->assertRedirect(route('kitchen.index'));
        $batch = CookingBatch::query()->firstOrFail();

        $this->travel(1)->minutes();
        $this->post(route('kitchen.batches.ready', $batch))
            ->assertRedirect(route('kitchen.index'));

        $this->assertDatabaseHas('cooking_batches', [
            'id' => $batch->id,
            'status' => 'ready',
        ]);

        $this->actingAs($kitchenStaff)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        // Only unfinished (cooking) batches are reset; a ready batch remains.
        $this->assertDatabaseHas('cooking_batches', [
            'id' => $batch->id,
            'status' => 'ready',
        ]);
    }

    public function test_kitchen_queue_does_not_reset_other_employee_batch_on_logout(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 16:00:00'));
        $kitchenStaffA = $this->createUser('Kitchen Staff');
        $kitchenStaffB = $this->createUser('Kitchen Staff');

        $scheduleA = Schedule::create([
            'employee_id' => $kitchenStaffA->employee_id,
            'schedule_date' => '2026-10-13',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'Active',
        ]);
        Attendance::create([
            'employee_id' => $kitchenStaffA->employee_id,
            'schedule_id' => $scheduleA->schedule_id,
            'date' => '2026-10-13',
            'time_in' => '09:00:00',
            'time_out' => null,
            'status' => 'Present',
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'proof_image' => null,
        ]);

        $this->actingAs($kitchenStaffA)
            ->post(route('kitchen.batches.store'), [
                'food_type' => 'chicken',
                'quantity' => 25,
                'duration_minutes' => 60,
            ])->assertRedirect(route('kitchen.index'));
        $this->actingAs($kitchenStaffB)
            ->post(route('kitchen.batches.store'), [
                'food_type' => 'chicken',
                'quantity' => 10,
                'duration_minutes' => 60,
            ])->assertRedirect(route('kitchen.index'));

        $this->assertDatabaseCount('cooking_batches', 2);

        $this->actingAs($kitchenStaffA)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        // Staff A's unfinished batch is reset; Staff B's batch remains.
        $this->assertDatabaseMissing('cooking_batches', [
            'employee_id' => $kitchenStaffA->employee_id,
            'status' => 'cooking',
        ]);
        $this->assertDatabaseHas('cooking_batches', [
            'employee_id' => $kitchenStaffB->employee_id,
            'status' => 'cooking',
        ]);
    }

    private function createUser(string $roleName): User
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
            'email' => strtolower(str_replace(' ', '.', $roleName)).User::query()->count().'@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);
    }

    private function createPendingOrder(?User $cashier = null): Order
    {
        $cashier ??= $this->createUser('Cashier');

        return Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => true,
            'total_amount' => 50,
            'status' => 'Pending',
        ]);
    }
}
