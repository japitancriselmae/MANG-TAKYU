<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_updates_inventory_and_records_the_transaction(): void
    {
        $manager = $this->createManager();
        $inventory = $this->createInventory(5);

        $this->actingAs($manager)
            ->post(route('stock-movements.store'), [
                'ingredient_id' => $inventory->ingredient_id,
                'movement_type' => 'Stock In',
                'quantity' => 4,
                'movement_date' => now()->format('Y-m-d H:i:s'),
                'reference' => 'Delivery 104',
                'remarks' => 'Received in good condition',
            ])
            ->assertRedirect(route('stock-movements.index'));

        $this->assertDatabaseHas('inventories', [
            'inventory_id' => $inventory->inventory_id,
            'quantity' => 9,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $inventory->ingredient_id,
            'user_id' => $manager->id,
            'movement_type' => 'Stock In',
            'quantity' => 4,
            'reference' => 'Delivery 104',
            'remarks' => 'Received in good condition',
        ]);
    }

    public function test_stock_out_cannot_reduce_inventory_below_zero(): void
    {
        $manager = $this->createManager();
        $inventory = $this->createInventory(5);

        $this->actingAs($manager)
            ->from(route('stock-movements.create'))
            ->post(route('stock-movements.store'), [
                'ingredient_id' => $inventory->ingredient_id,
                'movement_type' => 'Stock Out',
                'quantity' => 6,
                'movement_date' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('stock-movements.create'))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('inventories', [
            'inventory_id' => $inventory->inventory_id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_stock_movement_rejects_quantities_with_more_than_two_decimal_places(): void
    {
        $manager = $this->createManager();
        $inventory = $this->createInventory(5);

        $this->actingAs($manager)
            ->from(route('stock-movements.create'))
            ->post(route('stock-movements.store'), [
                'ingredient_id' => $inventory->ingredient_id,
                'movement_type' => 'Stock In',
                'quantity' => '1.234',
                'movement_date' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('stock-movements.create'))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('inventories', [
            'inventory_id' => $inventory->inventory_id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_stock_movement_rejects_future_dates(): void
    {
        $manager = $this->createManager();
        $inventory = $this->createInventory(5);

        $this->actingAs($manager)
            ->from(route('stock-movements.create'))
            ->post(route('stock-movements.store'), [
                'ingredient_id' => $inventory->ingredient_id,
                'movement_type' => 'Stock In',
                'quantity' => 1,
                'movement_date' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('stock-movements.create'))
            ->assertSessionHasErrors('movement_date');

        $this->assertDatabaseHas('inventories', [
            'inventory_id' => $inventory->inventory_id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_physical_adjustment_records_the_signed_stock_difference(): void
    {
        $manager = $this->createManager();
        $inventory = $this->createInventory(8);

        $this->actingAs($manager)
            ->post(route('stock-movements.store'), [
                'ingredient_id' => $inventory->ingredient_id,
                'movement_type' => 'Adjustment',
                'quantity' => 3,
                'movement_date' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('stock-movements.index'));

        $this->assertDatabaseHas('inventories', [
            'inventory_id' => $inventory->inventory_id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $inventory->ingredient_id,
            'movement_type' => 'Adjustment',
            'quantity' => -5,
        ]);
    }

    public function test_manual_inventory_edit_records_the_signed_difference_and_current_timestamp(): void
    {
        $this->travelTo('2026-11-02 12:00:00');
        $manager = $this->createManager();
        $inventory = $this->createInventory(5);

        $this->actingAs($manager)
            ->put(route('inventories.update', $inventory->inventory_id), [
                'ingredient_id' => $inventory->ingredient_id,
                'quantity' => 3,
                'minimum_stock' => 2,
                'last_updated' => '2025-01-01T10:00',
            ])
            ->assertRedirect(route('inventories.index'));

        $this->assertDatabaseHas('inventories', [
            'inventory_id' => $inventory->inventory_id,
            'quantity' => 3,
            'last_updated' => '2026-11-02 12:00:00',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $inventory->ingredient_id,
            'user_id' => $manager->id,
            'movement_type' => 'Adjustment',
            'quantity' => -2,
            'movement_date' => '2026-11-02 12:00:00',
            'remarks' => 'Manual inventory adjustment',
        ]);

        $movementLog = ActivityLog::query()
            ->where('action', 'stock.movement_recorded')
            ->firstOrFail();
        $this->assertSame($manager->id, $movementLog->user_id);
        $this->assertSame('Adjustment', $movementLog->new_values['movement_type']);
        $this->assertSame(-2.0, (float) $movementLog->new_values['quantity']);

        $inventoryLog = ActivityLog::query()
            ->where('action', 'inventory.updated')
            ->firstOrFail();
        $this->assertSame('5.00', $inventoryLog->old_values['quantity']);
        $this->assertSame('3.00', $inventoryLog->new_values['quantity']);
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

    private function createInventory(float $quantity): Inventory
    {
        $ingredient = Ingredient::create([
            'ingredient_name' => 'Chicken breast',
            'unit' => 'kg',
            'minimum_stock' => 2,
            'purchase_price' => 120,
            'status' => 'Active',
        ]);

        return Inventory::create([
            'ingredient_id' => $ingredient->ingredient_id,
            'quantity' => $quantity,
            'minimum_stock' => 2,
            'last_updated' => now(),
        ]);
    }
}
