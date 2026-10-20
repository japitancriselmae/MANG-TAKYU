<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_sees_todays_transaction_report_with_totals(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $cashier = $this->createEmployeeUser('Cashier');
        $manager = $this->createEmployeeUser('Manager');

        $this->recordPayment($cashier, '2026-10-14 09:15:00', 'Cash', 250.00, 50.00);
        $this->recordPayment($cashier, '2026-10-14 10:30:00', 'GCash', 180.00, 0.00);

        $response = $this->actingAs($manager)->get(route('reports.transactions'));

        // Cash nets 250 - 50 = 200; GCash nets 180 - 0 = 180; total net = 380.
        $response->assertOk();
        $response->assertViewHas('transactionCount', 2);
        $response->assertViewHas('netSales', 380.0);
        $response->assertViewHas('cashSales', 200.0);
        $response->assertViewHas('gcashSales', 180.0);
        $response->assertViewHas('totalCollected', 430.0);
        $response->assertViewHas('totalChangeGiven', 50.0);
        $response->assertSee('Daily Transaction Report');
        $response->assertSee('₱380.00');
        $response->assertSee('₱430.00');
        $response->assertSee('Print Report');
        $response->assertSee('Test Cashier');
        $response->assertSee('GCash');
    }

    public function test_report_only_includes_payments_from_the_selected_day(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $cashier = $this->createEmployeeUser('Cashier');
        $manager = $this->createEmployeeUser('Manager');

        $this->recordPayment($cashier, '2026-10-14 09:00:00', 'Cash', 100.00, 0.00);
        $this->recordPayment($cashier, '2026-10-13 09:00:00', 'Cash', 500.00, 0.00);
        $this->recordPayment($cashier, '2026-10-15 09:00:00', 'Cash', 999.00, 0.00);

        $response = $this->actingAs($manager)->get(route('reports.transactions'));

        $response->assertViewHas('transactionCount', 1);
        $response->assertViewHas('netSales', 100.0);
        $response->assertSee('₱100.00');
        $response->assertDontSee('₱500.00');
        $response->assertDontSee('₱999.00');
    }

    public function test_report_filters_to_a_specific_past_date(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $cashier = $this->createEmployeeUser('Cashier');
        $manager = $this->createEmployeeUser('Manager');

        $this->recordPayment($cashier, '2026-10-14 09:00:00', 'Cash', 100.00, 0.00);
        $this->recordPayment($cashier, '2026-10-12 08:00:00', 'GCash', 300.00, 20.00);

        $response = $this->actingAs($manager)
            ->get(route('reports.transactions', ['date' => '2026-10-12']));

        $response->assertOk();
        $response->assertViewHas('transactionCount', 1);
        $response->assertViewHas('netSales', 280.0);
        $response->assertViewHas('reportDate', function (Carbon $date): bool {
            return $date->toDateString() === '2026-10-12';
        });
        $response->assertSee('₱280.00');
        $response->assertSee('October 12, 2026');
        $response->assertDontSee('₱100.00');
    }

    public function test_report_shows_empty_state_when_no_transactions_on_the_day(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $manager = $this->createEmployeeUser('Manager');

        $response = $this->actingAs($manager)->get(route('reports.transactions'));

        $response->assertOk();
        $response->assertViewHas('transactionCount', 0);
        $response->assertViewHas('netSales', 0.0);
        $response->assertSee('No transactions recorded');
        $response->assertSee('There were no payments on October 14, 2026.');
    }

    public function test_report_rejects_a_future_date(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $manager = $this->createEmployeeUser('Manager');

        $this->actingAs($manager)
            ->get(route('reports.transactions', ['date' => '2026-10-20']))
            ->assertSessionHasErrors('date');
    }

    public function test_report_rejects_a_malformed_date(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $manager = $this->createEmployeeUser('Manager');

        $this->actingAs($manager)
            ->get(route('reports.transactions', ['date' => 'not-a-date']))
            ->assertSessionHasErrors('date');
    }

    public static function nonManagerRoles(): array
    {
        return [
            'cashier' => ['Cashier'],
            'kitchen staff' => ['Kitchen Staff'],
        ];
    }

    #[DataProvider('nonManagerRoles')]
    public function test_non_manager_roles_cannot_view_the_report(string $roleName): void
    {
        $user = $this->createEmployeeUser($roleName);

        $this->actingAs($user)
            ->get(route('reports.transactions'))
            ->assertForbidden();
    }

    public function test_admin_can_view_the_report(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $admin = $this->createEmployeeUser('Admin');

        $this->actingAs($admin)
            ->get(route('reports.transactions'))
            ->assertOk();
    }

    public function test_guest_is_redirected_to_login_from_the_report(): void
    {
        $this->get(route('reports.transactions'))
            ->assertRedirect(route('login'));
    }

    public function test_manager_sees_sales_report_with_totals_and_top_items(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $cashier = $this->createEmployeeUser('Cashier');
        $manager = $this->createEmployeeUser('Manager');

        $friedChicken = Product::create([
            'product_name' => 'Fried Chicken',
            'price' => 100.00,
            'status' => 'Active',
        ]);
        $rice = Product::create([
            'product_name' => 'Rice',
            'price' => 20.00,
            'status' => 'Active',
        ]);

        $order1 = $this->recordPayment($cashier, '2026-10-14 09:15:00', 'Cash', 250.00, 50.00);
        OrderItem::create([
            'order_id' => $order1->order_id,
            'product_id' => $friedChicken->product_id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'subtotal' => 200.00,
        ]);
        OrderItem::create([
            'order_id' => $order1->order_id,
            'product_id' => $rice->product_id,
            'quantity' => 1,
            'unit_price' => 20.00,
            'subtotal' => 20.00,
        ]);

        $this->recordPayment($cashier, '2026-10-14 10:30:00', 'GCash', 180.00, 0.00);

        $response = $this->actingAs($manager)->get(route('reports.sales'));

        // Cash nets 250 - 50 = 200; GCash nets 180 - 0 = 180; total net = 380.
        // Avg order = 380 / 2 = 190.
        $response->assertOk();
        $response->assertViewHas('transactionCount', 2);
        $response->assertViewHas('netSales', 380.0);
        $response->assertViewHas('averageOrder', 190.0);
        $response->assertSee('Total Sales');
        $response->assertSee('₱380.00');
        $response->assertSee('₱190.00');
        $response->assertSee('Fried Chicken');
        $response->assertSee('Rice');
        $response->assertSee('2'); // Fried Chicken quantity
        $response->assertSee('1'); // Rice quantity
    }

    public function test_sales_report_filters_to_a_specific_day(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $cashier = $this->createEmployeeUser('Cashier');
        $manager = $this->createEmployeeUser('Manager');

        $this->recordPayment($cashier, '2026-10-14 09:00:00', 'Cash', 100.00, 0.00);
        $this->recordPayment($cashier, '2026-10-13 09:00:00', 'Cash', 500.00, 0.00);

        $response = $this->actingAs($manager)->get(route('reports.sales', ['date' => '2026-10-13']));

        $response->assertOk();
        $response->assertViewHas('transactionCount', 1);
        $response->assertViewHas('netSales', 500.0);
        $response->assertSee('₱500.00');
        $response->assertSee('October 13, 2026');
        $response->assertDontSee('₱100.00');
    }

    public function test_sales_report_shows_empty_state_when_no_sales(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $manager = $this->createEmployeeUser('Manager');

        $response = $this->actingAs($manager)->get(route('reports.sales'));

        $response->assertOk();
        $response->assertViewHas('transactionCount', 0);
        $response->assertViewHas('netSales', 0.0);
        $response->assertViewHas('averageOrder', 0.0);
        $response->assertSee('No items sold');
    }

    #[DataProvider('nonManagerRoles')]
    public function test_non_manager_roles_cannot_view_sales_report(string $roleName): void
    {
        $user = $this->createEmployeeUser($roleName);

        $this->actingAs($user)
            ->get(route('reports.sales'))
            ->assertForbidden();
    }

    public function test_admin_can_view_sales_report(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 11:00:00'));

        $admin = $this->createEmployeeUser('Admin');

        $this->actingAs($admin)
            ->get(route('reports.sales'))
            ->assertOk();
    }

    public function test_guest_is_redirected_to_login_from_sales_report(): void
    {
        $this->get(route('reports.sales'))
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
            'email' => strtolower(str_replace(' ', '.', $roleName)).uniqid().'@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);
    }

    private function recordPayment(
        User $user,
        string $paymentDate,
        string $method,
        float $amountPaid,
        float $change
    ): Order {
        $order = Order::create([
            'employee_id' => $user->employee_id,
            'order_date' => $paymentDate,
            'order_type' => 'Take-out',
            'is_fresh_chicken' => false,
            'total_amount' => $amountPaid - $change,
            'status' => 'Completed',
        ]);

        Payment::create([
            'order_id' => $order->order_id,
            'payment_method' => $method,
            'amount_paid' => $amountPaid,
            'change_amount' => $change,
            'payment_date' => $paymentDate,
        ]);

        return $order;
    }
}
