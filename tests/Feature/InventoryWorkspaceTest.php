<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_sees_stock_item_management_in_inventory(): void
    {
        $manager = $this->createManager();
        $ingredient = Ingredient::create([
            'ingredient_name' => 'Chicken',
            'unit' => 'pcs',
            'minimum_stock' => 10,
            'status' => 'Active',
        ]);
        Inventory::create([
            'ingredient_id' => $ingredient->ingredient_id,
            'quantity' => 25,
            'minimum_stock' => 10,
            'last_updated' => now(),
        ]);

        $this->actingAs($manager)
            ->get(route('inventories.index'))
            ->assertSee('Stock Items')
            ->assertSee('Chicken')
            ->assertSee('Add Stock Item')
            ->assertDontSee('>Ingredients</span>', false)
            ->assertDontSee('>Recipes</span>', false);
    }

    public function test_manager_sees_product_recipe_management_in_inventory(): void
    {
        $manager = $this->createManager();
        $ingredient = Ingredient::create([
            'ingredient_name' => 'Chicken',
            'unit' => 'pcs',
            'minimum_stock' => 10,
            'status' => 'Active',
        ]);
        $product = Product::create([
            'product_name' => 'Crispy Chicken',
            'price' => 99.50,
            'status' => 'Active',
        ]);
        ProductIngredient::create([
            'product_id' => $product->product_id,
            'ingredient_id' => $ingredient->ingredient_id,
            'quantity_required' => 1,
        ]);

        $this->actingAs($manager)
            ->get(route('inventories.index', ['tab' => 'recipes']))
            ->assertSee('Product Recipes')
            ->assertSee('Crispy Chicken')
            ->assertSee('Chicken')
            ->assertSee('Add Recipe Ingredient');
    }

    public function test_ingredient_index_redirects_to_stock_items_inventory(): void
    {
        $manager = $this->createManager();

        $this->actingAs($manager)
            ->get(route('ingredients.index'))
            ->assertRedirect(route('inventories.index', ['tab' => 'stock']));
    }

    public function test_recipe_index_redirects_to_product_recipes_inventory(): void
    {
        $manager = $this->createManager();

        $this->actingAs($manager)
            ->get(route('product-ingredients.index'))
            ->assertRedirect(route('inventories.index', ['tab' => 'recipes']));
    }

    public function test_cashier_is_forbidden_from_inventory_workspace(): void
    {
        $cashier = $this->createUser('Cashier', 'cashier@example.com');

        $this->actingAs($cashier)
            ->get(route('inventories.index'))
            ->assertForbidden();
    }

    private function createManager(): User
    {
        return $this->createUser('Manager', 'manager@example.com');
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
