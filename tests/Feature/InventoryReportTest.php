<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_report_includes_opening_balances_and_applies_date_filters(): void
    {
        $this->travelTo('2026-11-02 12:00:00');
        $manager = $this->createUser('Manager', 'manager@example.com');
        $ingredient = Ingredient::create([
            'ingredient_name' => 'Chicken breast',
            'unit' => 'kg',
            'minimum_stock' => 2,
            'purchase_price' => 12.50,
            'status' => 'Active',
        ]);

        StockMovement::create([
            'ingredient_id' => $ingredient->ingredient_id,
            'user_id' => $manager->id,
            'movement_type' => 'Opening Balance',
            'quantity' => 4,
            'movement_date' => '2026-10-25 09:00:00',
            'reference' => 'Opening balance',
        ]);
        StockMovement::create([
            'ingredient_id' => $ingredient->ingredient_id,
            'user_id' => $manager->id,
            'movement_type' => 'Stock In',
            'quantity' => 2,
            'movement_date' => '2026-11-01 09:00:00',
            'reference' => 'Delivery 104',
        ]);
        StockMovement::create([
            'ingredient_id' => $ingredient->ingredient_id,
            'user_id' => $manager->id,
            'movement_type' => 'Stock In',
            'quantity' => 7,
            'movement_date' => '2026-10-01 09:00:00',
            'reference' => 'Older delivery',
        ]);

        $this->actingAs($manager)
            ->get(route('reports.inventory', [
                'type' => 'stock-in',
                'start_date' => '2026-10-20',
                'end_date' => '2026-11-01',
            ]))
            ->assertSee('Opening balance')
            ->assertSee('Delivery 104')
            ->assertDontSee('Older delivery')
            ->assertSee('2 record(s)');
    }

    public function test_inventory_valuation_report_uses_current_quantity_and_purchase_cost(): void
    {
        $manager = $this->createUser('Manager', 'manager@example.com');
        $ingredient = Ingredient::create([
            'ingredient_name' => 'Rice',
            'sku' => 'RICE-001',
            'unit' => 'kg',
            'minimum_stock' => 2,
            'purchase_price' => 12.50,
            'status' => 'Active',
        ]);
        Inventory::create([
            'ingredient_id' => $ingredient->ingredient_id,
            'quantity' => 4,
            'minimum_stock' => 2,
            'last_updated' => now(),
        ]);

        $this->actingAs($manager)
            ->get(route('reports.inventory', ['type' => 'valuation']))
            ->assertSee('₱50.00')
            ->assertSee('RICE-001');
    }

    public function test_low_stock_and_out_of_stock_reports_filter_current_inventory(): void
    {
        $manager = $this->createUser('Manager', 'manager@example.com');
        $lowStockIngredient = Ingredient::create([
            'ingredient_name' => 'Low stock item',
            'unit' => 'kg',
            'minimum_stock' => 5,
            'purchase_price' => 2,
            'status' => 'Active',
        ]);
        $outOfStockIngredient = Ingredient::create([
            'ingredient_name' => 'Out of stock item',
            'unit' => 'kg',
            'minimum_stock' => 5,
            'purchase_price' => 2,
            'status' => 'Active',
        ]);
        Inventory::create([
            'ingredient_id' => $lowStockIngredient->ingredient_id,
            'quantity' => 2,
            'minimum_stock' => 5,
            'last_updated' => now(),
        ]);
        Inventory::create([
            'ingredient_id' => $outOfStockIngredient->ingredient_id,
            'quantity' => 0,
            'minimum_stock' => 5,
            'last_updated' => now(),
        ]);

        $this->actingAs($manager)
            ->get(route('reports.inventory', ['type' => 'low-stock']))
            ->assertViewHas('inventories', function ($inventories): bool {
                return $inventories->total() === 1
                    && $inventories->first()->ingredient->ingredient_name === 'Low stock item';
            })
            ->assertSee('Low stock item')
            ->assertSee('Low stock');

        $this->get(route('reports.inventory', ['type' => 'out-of-stock']))
            ->assertViewHas('type', 'out-of-stock')
            ->assertViewHas('inventories', function ($inventories): bool {
                return $inventories->total() === 1
                    && $inventories->first()->ingredient->ingredient_name === 'Out of stock item';
            })
            ->assertSee('Out of stock item')
            ->assertSee('Out of stock');
    }

    public function test_cashier_cannot_view_inventory_reports(): void
    {
        $cashier = $this->createUser('Cashier', 'cashier@example.com');

        $this->actingAs($cashier)
            ->get(route('reports.inventory'))
            ->assertForbidden();
    }

    private function createUser(string $roleName, string $email): User
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
            'email' => $email,
            'password' => 'password123',
            'employee_id' => $employee->employee_id,
        ]);
    }
}
