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
use Tests\TestCase;

class OrderConfirmationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_page_shows_the_preparation_preference_and_no_search_box(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $this->createProductWithInventory();

        $response = $this->actingAs($cashier)->get(route('orders.create'));

        $response->assertOk();
        $response->assertSee('Chicken preparation preference');
        $response->assertSee('Available Cooked Chicken');
        $response->assertSee('Freshly Cooked Chicken');
        $response->assertDontSee('Search products');
        $response->assertDontSee('productSearch');
    }

    public function test_available_cooked_chicken_completes_the_order_and_deducts_stock(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $product = $this->createProductWithInventory();

        $response = $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => '0',
            'items' => [['product_id' => $product->product_id, 'quantity' => 2]],
            'payment_method' => 'Cash',
            'amount_paid' => 120,
        ]);

        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('orders.completed', $order));
        $this->assertSame('Completed', $order->fresh()->status);
        $this->assertFalse((bool) $order->fresh()->is_fresh_chicken);
        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $product->productIngredients()->value('ingredient_id'),
            'quantity' => 98,
        ]);
    }

    public function test_completion_page_shows_order_completed_and_create_another_order(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $product = $this->createProductWithInventory();

        $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Take-out',
            'is_fresh_chicken' => '0',
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            'payment_method' => 'Cash',
            'amount_paid' => 50,
        ]);

        $order = Order::query()->firstOrFail();
        $response = $this->actingAs($cashier)->get(route('orders.completed', $order));

        $response->assertOk();
        $response->assertSee('Order Completed');
        $response->assertSee('Available Cooked Chicken');
        $response->assertSee('Create Another Order');
        $response->assertSee('The stock was deducted and the payment was recorded successfully.');
        $response->assertDontSee('Order Waiting for Fresh Chicken');
    }

    public function test_freshly_cooked_chicken_waits_for_the_kitchen_timer_and_still_deducts_stock(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $product = $this->createProductWithInventory();

        $response = $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => '1',
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            'payment_method' => 'Cash',
            'amount_paid' => 50,
        ]);

        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('orders.completed', $order));

        // Stock is deducted even though the order is still waiting.
        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $product->productIngredients()->value('ingredient_id'),
            'quantity' => 99,
        ]);

        $this->assertSame('Pending', $order->fresh()->status);
        $this->assertTrue((bool) $order->fresh()->is_fresh_chicken);
        $this->assertDatabaseHas('payments', ['order_id' => $order->order_id]);

        $receipt = $this->actingAs($cashier)->get(route('orders.completed', $order));

        $receipt->assertOk();
        $receipt->assertSee('Order Waiting for Fresh Chicken');
        $receipt->assertSee('Freshly Cooked Chicken');
        $receipt->assertSee('Create Another Order');
        $receipt->assertDontSee('Order Completed');
    }

    public function test_waiting_fresh_chicken_order_becomes_ready_once_the_kitchen_timer_finishes(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $kitchenStaff = $this->createEmployeeUser('Kitchen Staff');
        $product = $this->createProductWithInventory();

        $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => '1',
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            'payment_method' => 'Cash',
            'amount_paid' => 50,
        ]);

        $order = Order::query()->firstOrFail();
        $this->assertSame('Pending', $order->fresh()->status);

        $this->actingAs($kitchenStaff)->post(route('kitchen.batches.store'), [
            'food_type' => 'chicken',
            'quantity' => 25,
            'duration_minutes' => 1,
        ])->assertRedirect(route('kitchen.index'));

        $batch = CookingBatch::query()->firstOrFail();
        $this->travel(2)->minutes();

        $this->actingAs($kitchenStaff)
            ->post(route('kitchen.batches.ready', $batch))
            ->assertRedirect(route('kitchen.index'));

        $this->assertSame('Ready', $order->fresh()->status);
    }

    public function test_completion_page_shows_the_running_kitchen_timer_for_a_waiting_order(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $kitchenStaff = $this->createEmployeeUser('Kitchen Staff');
        $product = $this->createProductWithInventory();

        $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => '1',
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            'payment_method' => 'Cash',
            'amount_paid' => 50,
        ]);

        $this->actingAs($kitchenStaff)->post(route('kitchen.batches.store'), [
            'food_type' => 'chicken',
            'quantity' => 10,
            'duration_minutes' => 20,
        ]);

        $order = Order::query()->firstOrFail();
        $response = $this->actingAs($cashier)->get(route('orders.completed', $order));

        $response->assertOk();
        $response->assertSee('Kitchen fresh chicken timer');
        $response->assertSee('fresh-chicken-countdown', false);
        $response->assertSee('data-duration-minutes="20"', false);
    }

    public function test_unpaid_order_cannot_open_the_completion_page(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $order = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 50,
            'status' => 'Pending',
        ]);

        $this->actingAs($cashier)
            ->get(route('orders.completed', $order))
            ->assertForbidden();
    }

    public function test_cashier_cannot_open_another_cashiers_completion_page(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $otherCashier = $this->createEmployeeUser('Cashier', 'other@example.com');

        $order = Order::create([
            'employee_id' => $otherCashier->employee_id,
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

        $this->actingAs($cashier)
            ->get(route('orders.completed', $order))
            ->assertForbidden();
    }

    public function test_guest_cannot_open_the_completion_page(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $order = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => false,
            'total_amount' => 50,
            'status' => 'Completed',
        ]);

        $this->get(route('orders.completed', $order))
            ->assertRedirect(route('login'));
    }

    public function test_inventory_shortage_blocks_the_order_and_leaves_stock_untouched(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $product = $this->createProductWithInventory();

        $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => '0',
            'items' => [['product_id' => $product->product_id, 'quantity' => 500]],
            'payment_method' => 'Cash',
            'amount_paid' => 25000,
        ])->assertSessionHasErrors('order');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $product->productIngredients()->value('ingredient_id'),
            'quantity' => 100,
        ]);
    }

    public function test_editing_a_paid_order_adding_items_deducts_more_stock_and_records_additional_payment(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $product = $this->createProductWithInventory();

        $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => '1',
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            'payment_method' => 'Cash',
            'amount_paid' => 50,
        ]);

        $order = Order::query()->firstOrFail();
        $ingredientId = $product->productIngredients()->value('ingredient_id');

        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $ingredientId,
            'quantity' => 99,
        ]);

        $response = $this->actingAs($cashier)->put(route('orders.update', $order), [
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => '1',
            'items' => [$product->product_id => ['quantity' => 3]],
            'additional_payment_method' => 'Cash',
            'additional_amount_paid' => 100,
        ]);

        $response->assertRedirect(route('orders.show', $order));

        // 100 initial - 3 now required.
        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $ingredientId,
            'quantity' => 97,
        ]);

        $this->assertSame(150.0, (float) $order->fresh()->total_amount);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->order_id,
            'amount_paid' => 100,
            'payment_method' => 'Cash',
        ]);
    }

    public function test_editing_a_paid_order_removing_items_returns_stock(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $product = $this->createProductWithInventory();

        $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Take-out',
            'is_fresh_chicken' => '1',
            'items' => [['product_id' => $product->product_id, 'quantity' => 4]],
            'payment_method' => 'Cash',
            'amount_paid' => 200,
        ]);

        $order = Order::query()->firstOrFail();
        $ingredientId = $product->productIngredients()->value('ingredient_id');

        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $ingredientId,
            'quantity' => 96,
        ]);

        $response = $this->actingAs($cashier)->put(route('orders.update', $order), [
            'order_type' => 'Take-out',
            'is_fresh_chicken' => '1',
            'items' => [$product->product_id => ['quantity' => 1]],
        ]);

        $response->assertRedirect(route('orders.show', $order));

        // Stock comes back: 96 + 3 returned = 99.
        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $ingredientId,
            'quantity' => 99,
        ]);

        $this->assertSame(50.0, (float) $order->fresh()->total_amount);

        // A shrinking total must not invent an extra payment.
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_raising_the_total_without_collecting_the_balance_is_rejected_and_changes_nothing(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $product = $this->createProductWithInventory();

        $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => '0',
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            'payment_method' => 'Cash',
            'amount_paid' => 50,
        ]);

        $order = Order::query()->firstOrFail();
        $ingredientId = $product->productIngredients()->value('ingredient_id');

        $this->actingAs($cashier)
            ->from(route('orders.edit', $order))
            ->put(route('orders.update', $order), [
                'order_type' => 'Dine-in',
                'is_fresh_chicken' => '0',
                'items' => [$product->product_id => ['quantity' => 3]],
            ])
            ->assertRedirect(route('orders.edit', $order))
            ->assertSessionHasErrors('order');

        // Nothing moved: same stock, same total, same payment count.
        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $ingredientId,
            'quantity' => 99,
        ]);
        $this->assertSame(50.0, (float) $order->fresh()->total_amount);
        $this->assertSame(1, $order->orderItems()->count());
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_paid_order_shows_the_fully_complete_action_and_the_fresh_chicken_timer(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $product = $this->createProductWithInventory();

        $this->actingAs($cashier)->post(route('orders.store'), [
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => '1',
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            'payment_method' => 'Cash',
            'amount_paid' => 50,
        ]);

        $kitchenStaff = $this->createEmployeeUser('Kitchen Staff');

        $this->actingAs($kitchenStaff)->post(route('kitchen.batches.store'), [
            'food_type' => 'chicken',
            'quantity' => 10,
            'duration_minutes' => 20,
        ]);

        $order = Order::query()->firstOrFail();
        $response = $this->actingAs($cashier)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('Kitchen fresh chicken timer');
        $response->assertSee('fresh-chicken-countdown', false);
        $response->assertSee('data-duration-minutes="20"', false);
        $response->assertSee('Edit Order');
    }

    public function test_ready_paid_order_offers_the_fully_complete_action(): void
    {
        $cashier = $this->createEmployeeUser('Cashier');
        $order = Order::create([
            'employee_id' => $cashier->employee_id,
            'order_date' => now(),
            'order_type' => 'Dine-in',
            'is_fresh_chicken' => true,
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

        $response = $this->actingAs($cashier)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('Fully Complete Order');
        $response->assertSee('Kitchen fresh chicken timer');

        $this->actingAs($cashier)
            ->post(route('orders.complete', $order))
            ->assertRedirect(route('orders.index', ['status_order' => $order->order_id]));

        $this->assertSame('Completed', $order->fresh()->status);

        // Once completed, the timer panel and the action disappear.
        $after = $this->actingAs($cashier)->get(route('orders.show', $order));
        $after->assertOk();
        $after->assertSee('✓ This order is complete');
        $after->assertDontSee('Fully Complete Order');
        $after->assertDontSee('Kitchen fresh chicken timer');
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

    private function createProductWithInventory(): Product
    {
        $ingredient = Ingredient::create([
            'ingredient_name' => 'Chicken',
            'unit' => 'pcs',
            'minimum_stock' => 10,
            'status' => 'Active',
        ]);

        Inventory::create([
            'ingredient_id' => $ingredient->ingredient_id,
            'quantity' => 100,
            'minimum_stock' => 10,
            'last_updated' => now(),
        ]);

        $product = Product::create([
            'product_name' => 'Fried Chicken',
            'price' => 50,
            'status' => 'Active',
        ]);

        ProductIngredient::create([
            'product_id' => $product->product_id,
            'ingredient_id' => $ingredient->ingredient_id,
            'quantity_required' => 1,
        ]);

        return $product;
    }
}
