<?php

namespace Tests\Feature;

use App\Models\CookingBatch;
use App\Models\Employee;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_add_products_and_checkout_with_cash(): void
    {
        $cashier = $this->createCashier();
        $product = $this->createProductWithInventory();

        $response = $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Dine-in',
            'items' => [
                ['product_id' => $product->product_id, 'quantity' => 2],
            ],
            'payment_method' => 'Cash',
            'amount_paid' => 120,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $response->assertRedirect(route('orders.completed', $order));
        $response->assertSessionHas('success', 'Order confirmed and payment recorded successfully.');
        $this->assertDatabaseHas('orders', [
            'order_id' => $order->order_id,
            'order_type' => 'Dine-in',
            'total_amount' => 100,
            'status' => 'Completed',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->order_id,
            'product_id' => $product->product_id,
            'quantity' => 2,
            'subtotal' => 100,
        ]);
        $this->assertDatabaseHas('payments', [
            'payment_id' => $payment->payment_id,
            'payment_method' => 'Cash',
            'amount_paid' => 120,
            'change_amount' => 20,
            'gcash_proof_path' => null,
        ]);
        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $product->productIngredients()->value('ingredient_id'),
            'quantity' => 98,
        ]);

        $this->actingAs($cashier)
            ->get(route('orders.index', ['status_order' => $order->order_id]))
            ->assertSee('data-auto-open="true"', false)
            ->assertSee('aria-label="View status for order #'.$order->order_id.'"', false);
    }

    public function test_fresh_chicken_order_waits_then_becomes_ready_and_completed_from_status_modal(): void
    {
        $cashier = $this->createCashier();
        $kitchenStaff = $this->createKitchenStaff();
        $product = $this->createProductWithInventory();

        $checkout = $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => true,
            'items' => [
                ['product_id' => $product->product_id, 'quantity' => 1],
            ],
            'payment_method' => 'Cash',
            'amount_paid' => 50,
        ]);
        $order = Order::query()->firstOrFail();

        $checkout->assertRedirect(route('orders.completed', $order));
        $this->assertSame('Pending', $order->status);
        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $product->productIngredients()->value('ingredient_id'),
            'quantity' => 99,
        ]);
        $this->actingAs($cashier)
            ->from(route('orders.index'))
            ->post(route('orders.confirm', $order))
            ->assertRedirect(route('orders.index'))
            ->assertSessionHasErrors('order');
        $this->assertSame('Pending', $order->fresh()->status);
        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $product->productIngredients()->value('ingredient_id'),
            'quantity' => 99,
        ]);

        $this->actingAs($kitchenStaff)->post(route('kitchen.batches.store'), [
            'food_type' => 'chicken',
            'quantity' => 25,
            'duration_minutes' => 1,
        ])->assertRedirect(route('kitchen.index'));
        $batch = CookingBatch::query()->firstOrFail();

        $this->travel(1)->minutes();
        $this->post(route('kitchen.batches.ready', $batch))
            ->assertRedirect(route('kitchen.index'));
        $this->assertSame('Ready', $order->fresh()->status);

        $this->actingAs($cashier)
            ->get(route('orders.index'))
            ->assertSee('data-order-status="Ready"', false)
            ->assertSee('Mark Completed');
        $this->post(route('orders.complete', $order))
            ->assertRedirect(route('orders.index', ['status_order' => $order->order_id]));

        $this->assertSame('Completed', $order->fresh()->status);
    }

    public function test_cashier_cannot_checkout_when_payment_is_less_than_cart_total(): void
    {
        $cashier = $this->createCashier();
        $product = $this->createProductWithInventory();

        $response = $this->actingAs($cashier)->from(route('orders.create'))->post(route('orders.store'), [
            'order_type' => 'Take-out',
            'items' => [
                ['product_id' => $product->product_id, 'quantity' => 2],
            ],
            'payment_method' => 'Cash',
            'amount_paid' => 50,
        ]);

        $response->assertRedirect(route('orders.create'));
        $response->assertSessionHasErrors('amount_paid');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_checkout_does_not_complete_or_charge_an_order_without_enough_inventory(): void
    {
        $cashier = $this->createCashier();
        $product = $this->createProductWithInventory();
        Inventory::query()->update(['quantity' => 0]);

        $response = $this->actingAs($cashier)
            ->from(route('orders.create'))
            ->post(route('orders.store'), [
                'order_type' => 'Dine-in',
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 1],
                ],
                'payment_method' => 'Cash',
                'amount_paid' => 50,
            ]);

        $response->assertRedirect(route('orders.create'));
        $response->assertSessionHasErrors('order');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $product->productIngredients()->value('ingredient_id'),
            'quantity' => 0,
        ]);
    }

    public function test_gcash_checkout_requires_and_privately_stores_payment_screenshot(): void
    {
        Storage::fake('local');
        $cashier = $this->createCashier();
        $product = $this->createProductWithInventory();

        $response = $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Take-out',
            'items' => [
                ['product_id' => $product->product_id, 'quantity' => 1],
            ],
            'payment_method' => 'GCash',
            'amount_paid' => 50,
            'gcash_proof' => UploadedFile::fake()->image('gcash-proof.png'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $payment = Payment::query()->firstOrFail();
        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('orders.completed', $order));
        $this->assertSame('Completed', $order->fresh()->status);
        $this->assertSame('GCash', $payment->payment_method);
        $this->assertNotNull($payment->gcash_proof_path);
        Storage::disk('local')->assertExists($payment->gcash_proof_path);

        $this->actingAs($cashier)
            ->get(route('payments.proof', $payment))
            ->assertOk();
    }

    public function test_gcash_checkout_cannot_be_submitted_without_screenshot(): void
    {
        $cashier = $this->createCashier();
        $product = Product::create([
            'product_name' => '1pc chicken',
            'price' => 50,
            'status' => 'Active',
        ]);

        $response = $this->actingAs($cashier)
            ->from(route('orders.create'))
            ->post(route('orders.store'), [
                'order_type' => 'Take-out',
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 1],
                ],
                'payment_method' => 'GCash',
                'amount_paid' => 50,
            ]);

        $response->assertRedirect(route('orders.create'));
        $response->assertSessionHasErrors('gcash_proof');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_gcash_amount_must_match_the_cart_total(): void
    {
        Storage::fake('local');
        $cashier = $this->createCashier();
        $product = Product::create([
            'product_name' => '1pc chicken',
            'price' => 50,
            'status' => 'Active',
        ]);

        $response = $this->actingAs($cashier)
            ->from(route('orders.create'))
            ->post(route('orders.store'), [
                'order_type' => 'Take-out',
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 1],
                ],
                'payment_method' => 'GCash',
                'amount_paid' => 51,
                'gcash_proof' => UploadedFile::fake()->image('gcash-proof.png'),
            ]);

        $response->assertRedirect(route('orders.create'));
        $response->assertSessionHasErrors('amount_paid');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        Storage::disk('local')->assertDirectoryEmpty('gcash-proofs');
    }

    public function test_cashier_cannot_view_another_cashiers_gcash_proof(): void
    {
        Storage::fake('local');
        $cashier = $this->createCashier();
        $otherCashier = $this->createCashier('other-cashier@example.com');
        $product = Product::create([
            'product_name' => '1pc chicken',
            'price' => 50,
            'status' => 'Active',
        ]);
        $order = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Take-out',
            'is_fresh_chicken' => false,
            'total_amount' => 50,
            'status' => 'Pending',
        ]);
        $order->orderItems()->create([
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 50,
            'subtotal' => 50,
        ]);
        $proofPath = UploadedFile::fake()->image('gcash-proof.png')->store('gcash-proofs', 'local');
        $payment = Payment::create([
            'order_id' => $order->order_id,
            'payment_method' => 'GCash',
            'amount_paid' => 50,
            'change_amount' => 0,
            'payment_date' => now(),
            'gcash_proof_path' => $proofPath,
        ]);

        $this->actingAs($otherCashier)
            ->get(route('payments.proof', $payment))
            ->assertForbidden();
    }

    public function test_gcash_payment_for_a_ready_order_also_requires_a_screenshot(): void
    {
        Storage::fake('local');
        $cashier = $this->createCashier();
        $order = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 50,
            'status' => 'Ready',
        ]);

        $response = $this->actingAs($cashier)
            ->post(route('payments.store'), [
                'order_id' => $order->order_id,
                'payment_method' => 'GCash',
                'amount_paid' => 50,
            ]);

        $response->assertSessionHasErrors('gcash_proof');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_gcash_payment_for_a_ready_order_must_match_the_total(): void
    {
        Storage::fake('local');
        $cashier = $this->createCashier();
        $order = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 50,
            'status' => 'Ready',
        ]);

        $response = $this->actingAs($cashier)
            ->post(route('payments.store'), [
                'order_id' => $order->order_id,
                'payment_method' => 'GCash',
                'amount_paid' => 51,
                'gcash_proof' => UploadedFile::fake()->image('gcash-proof.png'),
            ]);

        $response->assertSessionHasErrors('amount_paid');
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('Ready', $order->fresh()->status);
        Storage::disk('local')->assertDirectoryEmpty('gcash-proofs');
    }

    public function test_gcash_payment_for_a_ready_order_saves_proof_and_marks_it_paid(): void
    {
        Storage::fake('local');
        $cashier = $this->createCashier();
        $order = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 50,
            'status' => 'Ready',
        ]);

        $response = $this->actingAs($cashier)
            ->post(route('payments.store'), [
                'order_id' => $order->order_id,
                'payment_method' => 'GCash',
                'amount_paid' => 50,
                'gcash_proof' => UploadedFile::fake()->image('gcash-proof.png'),
            ]);

        $payment = Payment::query()->firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame('Paid', $order->fresh()->status);
        $this->assertNotNull($payment->gcash_proof_path);
        Storage::disk('local')->assertExists($payment->gcash_proof_path);
    }

    public function test_paid_pending_order_can_be_edited(): void
    {
        $cashier = $this->createCashier();
        $order = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 50,
            'status' => 'Pending',
        ]);
        Payment::create([
            'order_id' => $order->order_id,
            'payment_method' => 'Cash',
            'amount_paid' => 50,
            'change_amount' => 0,
            'payment_date' => now(),
        ]);

        $response = $this->actingAs($cashier)
            ->get(route('orders.edit', $order));

        $response->assertOk();
        $response->assertSee('Order Summary');
        $response->assertSee('Additional payment');
    }

    public function test_completed_order_cannot_be_edited(): void
    {
        $cashier = $this->createCashier();
        $order = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 50,
            'status' => 'Completed',
        ]);
        Payment::create([
            'order_id' => $order->order_id,
            'payment_method' => 'Cash',
            'amount_paid' => 50,
            'change_amount' => 0,
            'payment_date' => now(),
        ]);

        $response = $this->actingAs($cashier)
            ->from(route('orders.index'))
            ->get(route('orders.edit', $order));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHasErrors('order');
    }

    public function test_upfront_paid_order_can_be_completed_after_it_is_ready(): void
    {
        $cashier = $this->createCashier();
        $order = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 50,
            'status' => 'Ready',
        ]);
        Payment::create([
            'order_id' => $order->order_id,
            'payment_method' => 'Cash',
            'amount_paid' => 50,
            'change_amount' => 0,
            'payment_date' => now(),
        ]);

        $response = $this->actingAs($cashier)
            ->post(route('orders.complete', $order));

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'order_id' => $order->order_id,
            'status' => 'Completed',
        ]);
    }

    public function test_order_and_transaction_lists_can_be_filtered(): void
    {
        $cashier = $this->createCashier();
        $pendingOrder = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 50,
            'status' => 'Pending',
        ]);
        $completedOrder = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now()->subMinute(),
            'order_type' => 'Take-out',
            'is_fresh_chicken' => false,
            'total_amount' => 75,
            'status' => 'Completed',
        ]);
        Payment::create([
            'order_id' => $pendingOrder->order_id,
            'payment_method' => 'Cash',
            'amount_paid' => 50,
            'change_amount' => 0,
            'payment_date' => now(),
        ]);
        Payment::create([
            'order_id' => $completedOrder->order_id,
            'payment_method' => 'GCash',
            'amount_paid' => 75,
            'change_amount' => 0,
            'payment_date' => now()->subMinute(),
        ]);

        $this->actingAs($cashier)
            ->get(route('orders.index', ['filter' => 'pending']))
            ->assertViewHas('orders', fn ($orders): bool => $orders->count() === 1
                && $orders->first()->order_id === $pendingOrder->order_id);

        $this->get(route('payments.index', ['method' => 'GCash']))
            ->assertViewHas('payments', fn ($payments): bool => $payments->count() === 1
                && $payments->first()->order_id === $completedOrder->order_id)
            ->assertViewHas('methodFilter', 'GCash');
    }

    public function test_new_order_screen_shows_service_selection_in_the_order_summary_and_cart_checkout(): void
    {
        $cashier = $this->createCashier();
        Product::create([
            'product_name' => '1pc chicken',
            'price' => 50,
            'status' => 'Active',
        ]);

        $response = $this->actingAs($cashier)->get(route('orders.create'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Step 1 — Choose a product to order',
            'Read back the order',
            'Ask: Dine-in or take-out?',
            'Chicken preparation preference',
        ]);
        $response->assertSee('Final purchase amount');
        $response->assertSee('Change');
        $response->assertSee('Suggestive sell');
        $response->assertSee('Review Order & Payment', false);
        $response->assertSee('name="payment_method"', false);
        $response->assertSee('name="amount_paid"', false);
    }

    public function test_cashier_order_review_shows_the_running_chicken_batch_timer(): void
    {
        $cashier = $this->createCashier();
        CookingBatch::create([
            'employee_id' => $cashier->employee_id,
            'food_type' => 'chicken',
            'quantity' => 25,
            'duration_minutes' => 18,
            'status' => 'cooking',
            'started_at' => now()->subMinutes(3),
        ]);

        $response = $this->actingAs($cashier)->get(route('orders.create'));

        $response->assertSee('Chicken is being cooked. The current batch timer:');
        $response->assertSee('25 pcs chicken');
        $response->assertSee('data-duration-minutes="18"', false);
        $response->assertSee('data-started-at="', false);
    }

    public function test_manager_can_only_view_the_order_list_and_cannot_access_order_taking(): void
    {
        $manager = $this->createManager();
        $cashier = $this->createCashier();
        $order = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 50,
            'status' => 'Pending',
        ]);
        $this->actingAs($manager);

        $this->get(route('orders.index'))
            ->assertSee('Order List')
            ->assertSee('#'.$order->order_id)
            ->assertDontSee('New Order')
            ->assertDontSee('Create First Order')
            ->assertDontSee('Actions')
            ->assertDontSee('>View</a>', false)
            ->assertDontSee('>Edit</a>', false)
            ->assertDontSee('>Confirm</button>', false);

        $this->get(route('orders.create'))->assertForbidden();
        $this->post(route('orders.store'))->assertForbidden();
        $this->get(route('orders.show', $order))->assertForbidden();
    }

    public function test_manager_payment_details_do_not_show_a_view_order_button(): void
    {
        $manager = $this->createManager();
        $cashier = $this->createCashier();
        $order = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 50,
            'status' => 'Paid',
        ]);
        $payment = Payment::create([
            'order_id' => $order->order_id,
            'payment_method' => 'Cash',
            'amount_paid' => 50,
            'change_amount' => 0,
            'payment_date' => now(),
        ]);

        $this->actingAs($manager)
            ->get(route('payments.show', $payment))
            ->assertSee('Payment Information')
            ->assertDontSee('View Order');
    }

    private function createCashier(string $email = 'cashier@example.com'): User
    {
        $role = Role::create(['role_name' => 'Cashier']);
        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Test',
            'last_name' => 'Cashier',
            'status' => 'Active',
        ]);

        return User::create([
            'name' => 'Test Cashier',
            'email' => $email,
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);
    }

    private function createKitchenStaff(): User
    {
        $role = Role::create(['role_name' => 'Kitchen Staff']);
        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Test',
            'last_name' => 'Kitchen',
            'status' => 'Active',
        ]);

        return User::create([
            'name' => 'Test Kitchen',
            'email' => 'kitchen@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);
    }

    private function createProductWithInventory(): Product
    {
        $product = Product::create([
            'product_name' => '1pc chicken',
            'price' => 50,
            'status' => 'Active',
        ]);
        $ingredient = Ingredient::create([
            'ingredient_name' => 'Chicken',
            'unit' => 'pcs',
            'minimum_stock' => 1,
            'status' => 'Active',
        ]);
        Inventory::create([
            'ingredient_id' => $ingredient->ingredient_id,
            'quantity' => 100,
            'last_updated' => now(),
        ]);
        ProductIngredient::create([
            'product_id' => $product->product_id,
            'ingredient_id' => $ingredient->ingredient_id,
            'quantity_required' => 1,
        ]);

        return $product;
    }

    private function createManager(): User
    {
        $role = Role::create(['role_name' => 'Manager']);
        $employee = Employee::create([
            'role_id' => $role->role_id,
            'first_name' => 'Test',
            'last_name' => 'Manager',
            'status' => 'Active',
        ]);

        return User::create([
            'name' => 'Test Manager',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);
    }
}
