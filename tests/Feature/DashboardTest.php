<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_today_metrics_without_sales_comparison_for_cashier(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 11:00:00'));

        $role = Role::create(['role_name' => 'Cashier']);
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

        Order::create([
            'employee_id' => $employee->employee_id,
            'order_date' => '2026-10-12 09:00:00',
            'order_type' => 'Take-out',
            'is_fresh_chicken' => false,
            'total_amount' => 150,
            'status' => 'Pending',
        ]);
        $completedOrder = Order::create([
            'employee_id' => $employee->employee_id,
            'order_date' => '2026-10-12 10:00:00',
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 200,
            'status' => 'Completed',
        ]);
        $yesterdayOrder = Order::create([
            'employee_id' => $employee->employee_id,
            'order_date' => '2026-10-11 23:59:59',
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 300,
            'status' => 'Completed',
        ]);

        Payment::create([
            'order_id' => $completedOrder->order_id,
            'payment_method' => 'Cash',
            'amount_paid' => 250,
            'change_amount' => 50,
            'payment_date' => '2026-10-12 10:05:00',
        ]);
        Payment::create([
            'order_id' => $yesterdayOrder->order_id,
            'payment_method' => 'Cash',
            'amount_paid' => 350,
            'change_amount' => 50,
            'payment_date' => '2026-10-11 23:59:59',
        ]);
        Payment::create([
            'order_id' => $yesterdayOrder->order_id,
            'payment_method' => 'GCash',
            'amount_paid' => 400,
            'change_amount' => 40,
            'payment_date' => '2026-10-05 12:00:00',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertViewHas('totalOrders', 2);
        $response->assertViewHas('pendingOrders', 1);
        $response->assertViewHas('completedOrders', 1);
        $response->assertViewHas('totalTransactions', 1);
        $response->assertViewHas('salesToday', 200.0);
        $response->assertSee('Orders Today');
        $response->assertSee('Sales Today');
        $response->assertSee('Pending Orders');
        $response->assertSee('Completed Orders');
        $response->assertSee('Total Transactions');
        $response->assertDontSee('Sales Comparison');
        $response->assertViewMissing('salesComparison');
        $response->assertSee('dashboard-date', false);
        $response->assertSee('dashboard-time', false);
        $response->assertDontSee('Quick Actions');
    }

    #[DataProvider('staffRoles')]
    public function test_dashboard_hides_sales_comparison_from_staff_roles(string $roleName): void
    {
        $user = $this->createEmployeeUser($roleName);

        $response = $this->actingAs($user)->get('/');

        $response->assertDontSee('Sales Comparison');
        $response->assertViewMissing('salesComparison');
    }

    public function test_dashboard_shows_sales_comparison_to_manager(): void
    {
        $manager = $this->createEmployeeUser('Manager');

        $response = $this->actingAs($manager)->get('/');

        $response->assertSee('Sales Comparison');
        $response->assertViewHas('salesComparison');
    }

    public function test_dashboard_compares_selected_days_with_the_previous_equal_length_period(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 11:00:00'));
        $user = $this->createEmployeeUser('Manager');

        $this->recordPayment($user, '2026-10-10', 100);
        $this->recordPayment($user, '2026-10-11', 200);
        $this->recordPayment($user, '2026-10-12', 300);
        $this->recordPayment($user, '2026-10-07', 40);
        $this->recordPayment($user, '2026-10-08', 50);
        $this->recordPayment($user, '2026-10-09', 60);

        $response = $this->actingAs($user)->get('/?start_date=2026-10-10&end_date=2026-10-12');

        $response->assertViewHas('currentSalesTotal', 600.0);
        $response->assertViewHas('previousSalesTotal', 150.0);
        $response->assertViewHas('salesComparison', function (array $comparison): bool {
            return count($comparison) === 3
                && $comparison[0]['current_sales'] === 100.0
                && $comparison[0]['previous_sales'] === 40.0
                && $comparison[2]['current_sales'] === 300.0
                && $comparison[2]['previous_sales'] === 60.0;
        });
        $response->assertSee('Selected dates (Oct 10, 2026 – Oct 12, 2026)');
        $response->assertSee('Previous period (Oct 7, 2026 – Oct 9, 2026)');
    }

    public function test_dashboard_custom_dates_are_plotted_by_day(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 11:00:00'));
        $user = $this->createEmployeeUser('Manager');

        $this->recordPayment($user, '2026-10-07', 100);
        $this->recordPayment($user, '2026-10-12', 200);
        $this->recordPayment($user, '2026-10-01', 10);
        $this->recordPayment($user, '2026-10-06', 20);

        $response = $this->actingAs($user)->get('/?start_date=2026-10-07&end_date=2026-10-12');

        $response->assertViewHas('currentSalesTotal', 300.0);
        $response->assertViewHas('previousSalesTotal', 30.0);
        $response->assertViewHas('salesComparison', function (array $comparison): bool {
            return count($comparison) === 6
                && $comparison[0]['current_sales'] === 100.0
                && $comparison[0]['previous_sales'] === 10.0
                && $comparison[5]['current_sales'] === 200.0
                && $comparison[5]['previous_sales'] === 20.0;
        });
        $response->assertSee('Selected dates (Oct 7, 2026 – Oct 12, 2026)');
        $response->assertSee('Previous period (Oct 1, 2026 – Oct 6, 2026)');
    }

    public function test_dashboard_uses_from_and_to_dates_for_the_selected_period(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 11:00:00'));
        $user = $this->createEmployeeUser('Manager');

        $this->recordPayment($user, '2026-10-12', 200);
        $this->recordPayment($user, '2026-10-11', 80);

        $response = $this->actingAs($user)->get('/?start_date=2026-10-12&end_date=2026-10-12');

        $response->assertViewHas('currentSalesTotal', 200.0);
        $response->assertViewHas('previousSalesTotal', 80.0);
        $response->assertViewHas('salesComparison', function (array $comparison): bool {
            return count($comparison) === 1
                && $comparison[0]['current_sales'] === 200.0
                && $comparison[0]['previous_sales'] === 80.0;
        });
        $response->assertSee('Selected dates (Oct 12, 2026 – Oct 12, 2026)');
        $response->assertSee('Previous period (Oct 11, 2026 – Oct 11, 2026)');
    }

    public function test_dashboard_rejects_custom_date_ranges_that_end_before_the_start(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 11:00:00'));
        $user = $this->createEmployeeUser('Manager');

        $response = $this->actingAs($user)->get('/?start_date=2026-10-10&end_date=2026-10-09');

        $response->assertSessionHasErrors('end_date');
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_kitchen_queue_is_only_shown_to_kitchen_staff(): void
    {
        $manager = $this->createEmployeeUser('Manager');
        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertDontSee('Kitchen Queue');

        $kitchenStaff = $this->createEmployeeUser('Kitchen Staff');
        $this->actingAs($kitchenStaff)
            ->get(route('dashboard'))
            ->assertSee('Kitchen Queue');
    }

    public static function staffRoles(): array
    {
        return [
            'cashier' => ['Cashier'],
            'kitchen staff' => ['Kitchen Staff'],
        ];
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

    private function recordPayment(User $user, string $date, float $amount): void
    {
        $order = Order::create([
            'employee_id' => $user->employee_id,
            'order_date' => $date.' 10:00:00',
            'order_type' => 'Take-out',
            'is_fresh_chicken' => false,
            'total_amount' => $amount,
            'status' => 'Completed',
        ]);

        Payment::create([
            'order_id' => $order->order_id,
            'payment_method' => 'Cash',
            'amount_paid' => $amount,
            'change_amount' => 0,
            'payment_date' => $date.' 10:05:00',
        ]);
    }
}
