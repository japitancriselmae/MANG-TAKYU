<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Ingredient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_creates_inventory_and_audits_the_opening_balance(): void
    {
        $this->travelTo('2026-11-02 12:00:00');
        $manager = $this->createManager();
        $ingredient = Ingredient::create([
            'ingredient_name' => 'Chicken breast',
            'unit' => 'kg',
            'minimum_stock' => 2.50,
            'purchase_price' => 12.50,
            'status' => 'Active',
        ]);

        $this->actingAs($manager)
            ->post(route('inventories.store'), [
                'ingredient_id' => $ingredient->ingredient_id,
                'quantity' => 6.25,
                'minimum_stock' => 2.50,
                'last_updated' => '2026-11-02T12:00',
            ])
            ->assertRedirect(route('inventories.index'));

        $this->assertDatabaseHas('inventories', [
            'ingredient_id' => $ingredient->ingredient_id,
            'quantity' => 6.25,
            'minimum_stock' => 2.50,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $ingredient->ingredient_id,
            'user_id' => $manager->id,
            'movement_type' => 'Opening Balance',
            'quantity' => 6.25,
            'movement_date' => '2026-11-02 12:00:00',
            'reference' => 'Initial stock',
            'remarks' => 'Opening balance',
        ]);
    }

    public function test_inventory_creation_rejects_future_dates_and_excess_precision(): void
    {
        $this->travelTo('2026-11-02 12:00:00');
        $manager = $this->createManager();
        $ingredient = Ingredient::create([
            'ingredient_name' => 'Rice',
            'unit' => 'kg',
            'minimum_stock' => 2,
            'purchase_price' => 12.50,
            'status' => 'Active',
        ]);

        $this->actingAs($manager)
            ->from(route('inventories.create'))
            ->post(route('inventories.store'), [
                'ingredient_id' => $ingredient->ingredient_id,
                'quantity' => '1.234',
                'minimum_stock' => 2,
                'last_updated' => '2026-11-03T12:00',
            ])
            ->assertRedirect(route('inventories.create'))
            ->assertSessionHasErrors(['quantity', 'last_updated']);

        $this->assertDatabaseCount('inventories', 0);
        $this->assertDatabaseCount('stock_movements', 0);
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
