<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_and_search_inventory_categories(): void
    {
        $manager = $this->createUser('Manager', 'manager@example.com');

        $this->actingAs($manager)
            ->post(route('inventory-categories.store'), [
                'name' => 'Dry goods',
                'description' => 'Shelf-stable ingredients',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('inventory_categories', [
            'name' => 'Dry goods',
            'description' => 'Shelf-stable ingredients',
        ]);

        $this->actingAs($manager)
            ->get(route('inventory-categories.index', ['search' => 'Dry']))
            ->assertSee('Dry goods')
            ->assertSee('Shelf-stable ingredients');
    }

    public function test_manager_can_update_and_delete_an_empty_category(): void
    {
        $manager = $this->createUser('Manager', 'manager@example.com');
        $category = InventoryCategory::create(['name' => 'Pantry']);

        $this->actingAs($manager)
            ->put(route('inventory-categories.update', $category), [
                'name' => 'Dry goods',
                'description' => 'Shelf-stable stock',
            ])
            ->assertRedirect(route('inventory-categories.show', $category));

        $this->assertDatabaseHas('inventory_categories', [
            'id' => $category->id,
            'name' => 'Dry goods',
            'description' => 'Shelf-stable stock',
        ]);

        $this->delete(route('inventory-categories.destroy', $category))
            ->assertRedirect(route('inventory-categories.index'));

        $this->assertDatabaseMissing('inventory_categories', ['id' => $category->id]);
    }

    public function test_category_page_counts_and_shows_stock_items(): void
    {
        $manager = $this->createUser('Manager', 'manager@example.com');
        $category = InventoryCategory::create(['name' => 'Proteins']);
        $ingredient = Ingredient::create([
            'inventory_category_id' => $category->id,
            'ingredient_name' => 'Chicken breast',
            'sku' => 'CH-001',
            'unit' => 'kg',
            'minimum_stock' => 3,
            'purchase_price' => 120,
            'status' => 'Active',
        ]);
        Inventory::create([
            'ingredient_id' => $ingredient->ingredient_id,
            'quantity' => 8,
            'minimum_stock' => 3,
            'last_updated' => now(),
        ]);

        $this->actingAs($manager)
            ->get(route('inventory-categories.show', $category))
            ->assertSee('Chicken breast')
            ->assertSee('CH-001')
            ->assertSee('8.00 kg');
    }

    public function test_category_with_stock_items_cannot_be_deleted(): void
    {
        $manager = $this->createUser('Manager', 'manager@example.com');
        $category = InventoryCategory::create(['name' => 'Produce']);
        Ingredient::create([
            'inventory_category_id' => $category->id,
            'ingredient_name' => 'Lettuce',
            'unit' => 'pcs',
            'minimum_stock' => 1,
            'purchase_price' => 20,
            'status' => 'Active',
        ]);

        $this->actingAs($manager)
            ->delete(route('inventory-categories.destroy', $category))
            ->assertSessionHasErrors('category');

        $this->assertModelExists($category);
    }

    public function test_cashier_cannot_access_inventory_categories(): void
    {
        $cashier = $this->createUser('Cashier', 'cashier@example.com');

        $this->actingAs($cashier)
            ->get(route('inventory-categories.index'))
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
