<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionDateFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_default_to_every_day_when_no_date_is_given(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $cashier = $this->createEmployeeUser('Cashier');
        $manager = $this->createEmployeeUser('Manager');

        $this->recordPayment($cashier, '2026-10-14 09:00:00');
        $this->recordPayment($cashier, '2026-10-12 09:00:00');
        $this->recordPayment($cashier, '2026-09-30 09:00:00');

        $response = $this->actingAs($manager)->get(route('payments.index'));

        $response->assertOk();
        $response->assertViewHas('allPaymentsCount', 3);
        $response->assertViewHas('filterDate', null);
        $response->assertDontSee('Showing transactions for');
    }

    public function test_transactions_can_be_filtered_to_a_single_day(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $cashier = $this->createEmployeeUser('Cashier');
        $manager = $this->createEmployeeUser('Manager');

        $this->recordPayment($cashier, '2026-10-14 09:00:00');
        $this->recordPayment($cashier, '2026-10-14 15:30:00');
        $this->recordPayment($cashier, '2026-10-13 09:00:00');
        $this->recordPayment($cashier, '2026-10-15 09:00:00');

        $response = $this->actingAs($manager)
            ->get(route('payments.index', ['date' => '2026-10-14']));

        $response->assertOk();
        $response->assertViewHas('allPaymentsCount', 2);
        $response->assertViewHas('payments', fn ($payments): bool => $payments->count() === 2);
        $response->assertViewHas('filterDate', function (Carbon $date): bool {
            return $date->toDateString() === '2026-10-14';
        });
        $response->assertSee('Showing transactions for');
        $response->assertSee('October 14, 2026');
    }

    public function test_day_filter_includes_payments_at_the_start_and_end_of_the_day(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $cashier = $this->createEmployeeUser('Cashier');
        $manager = $this->createEmployeeUser('Manager');

        $this->recordPayment($cashier, '2026-10-14 00:00:01');
        $this->recordPayment($cashier, '2026-10-14 23:59:59');
        $this->recordPayment($cashier, '2026-10-13 23:59:59');
        $this->recordPayment($cashier, '2026-10-15 00:00:00');

        $response = $this->actingAs($manager)
            ->get(route('payments.index', ['date' => '2026-10-14']));

        $response->assertViewHas('allPaymentsCount', 2);
    }

    public function test_day_filter_combines_with_the_payment_method_filter(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $cashier = $this->createEmployeeUser('Cashier');
        $manager = $this->createEmployeeUser('Manager');

        $this->recordPayment($cashier, '2026-10-14 09:00:00', 'Cash');
        $this->recordPayment($cashier, '2026-10-14 10:00:00', 'GCash');
        $this->recordPayment($cashier, '2026-10-13 11:00:00', 'GCash');

        $response = $this->actingAs($manager)->get(route('payments.index', [
            'date' => '2026-10-14',
            'method' => 'GCash',
        ]));

        $response->assertOk();
        $response->assertViewHas('payments', fn ($payments): bool => $payments->count() === 1);
        $response->assertViewHas('allPaymentsCount', 2);
        $response->assertViewHas('methodFilter', 'GCash');
    }

    public function test_switching_method_keeps_the_selected_day(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $cashier = $this->createEmployeeUser('Cashier');
        $manager = $this->createEmployeeUser('Manager');

        $this->recordPayment($cashier, '2026-10-14 09:00:00', 'Cash');
        $this->recordPayment($cashier, '2026-10-13 10:00:00', 'Cash');

        $response = $this->actingAs($manager)->get(route('payments.index', [
            'date' => '2026-10-14',
            'method' => 'Cash',
        ]));

        $response->assertSee('Showing transactions for');
        $response->assertSee('October 14, 2026');
        $response->assertViewHas('allPaymentsCount', 1);
    }

    public function test_day_with_no_transactions_shows_a_day_specific_empty_state(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $cashier = $this->createEmployeeUser('Cashier');
        $manager = $this->createEmployeeUser('Manager');

        $this->recordPayment($cashier, '2026-10-13 09:00:00');

        $response = $this->actingAs($manager)
            ->get(route('payments.index', ['date' => '2026-10-14']));

        $response->assertOk();
        $response->assertViewHas('allPaymentsCount', 0);
        $response->assertSee('There were no transactions on October 14, 2026.');
        $response->assertSee('View All Transactions');
    }

    public function test_transactions_reject_a_future_date(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $manager = $this->createEmployeeUser('Manager');

        $this->actingAs($manager)
            ->get(route('payments.index', ['date' => '2026-10-20']))
            ->assertSessionHasErrors('date');
    }

    public function test_transactions_reject_a_malformed_date(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $manager = $this->createEmployeeUser('Manager');

        $this->actingAs($manager)
            ->get(route('payments.index', ['date' => '14/10/2026']))
            ->assertSessionHasErrors('date');
    }

    public function test_cashier_day_filter_stays_scoped_to_their_own_orders(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $cashier = $this->createEmployeeUser('Cashier');
        $otherCashier = $this->createEmployeeUser('Cashier', 'other.cashier@example.com');

        $this->recordPayment($cashier, '2026-10-14 09:00:00');
        $this->recordPayment($otherCashier, '2026-10-14 10:00:00');
        $this->recordPayment($otherCashier, '2026-10-13 10:00:00');

        $response = $this->actingAs($cashier)
            ->get(route('payments.index', ['date' => '2026-10-14']));

        $response->assertOk();
        $response->assertViewHas('allPaymentsCount', 1);
        $response->assertViewHas('payments', fn ($payments): bool => $payments->count() === 1);
    }

    public function test_kitchen_staff_cannot_view_transactions(): void
    {
        $kitchenStaff = $this->createEmployeeUser('Kitchen Staff');

        $this->actingAs($kitchenStaff)
            ->get(route('payments.index'))
            ->assertForbidden();
    }

    private function createEmployeeUser(string $roleName, ?string $email = null): User
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
            'email' => $email ?? strtolower(str_replace(' ', '.', $roleName)).uniqid().'@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);
    }

    private function recordPayment(User $user, string $paymentDate, string $method = 'Cash'): Payment
    {
        $order = Order::create([
            'employee_id' => $user->employee_id,
            'order_date' => $paymentDate,
            'order_type' => 'Take-out',
            'is_fresh_chicken' => false,
            'total_amount' => 100,
            'status' => 'Completed',
        ]);

        return Payment::create([
            'order_id' => $order->order_id,
            'payment_method' => $method,
            'amount_paid' => 100,
            'change_amount' => 0,
            'payment_date' => $paymentDate,
        ]);
    }
}
