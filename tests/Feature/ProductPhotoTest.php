<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_product_with_photo(): void
    {
        Storage::fake('public');
        $manager = $this->createManager();

        $response = $this->actingAs($manager)->post(route('products.store'), [
            'product_name' => 'Crispy Chicken',
            'price' => 99.50,
            'status' => 'Active',
            'image' => UploadedFile::fake()->image('chicken.png'),
        ]);

        $product = Product::query()->firstOrFail();
        $response->assertRedirect(route('products.index'));
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_manager_can_replace_or_remove_a_product_photo(): void
    {
        Storage::fake('public');
        $manager = $this->createManager();
        $oldImagePath = UploadedFile::fake()->image('old.png')->store('products', 'public');
        $product = Product::create([
            'product_name' => 'Crispy Chicken',
            'image_path' => $oldImagePath,
            'price' => 99.50,
            'status' => 'Active',
        ]);

        $response = $this->actingAs($manager)->put(route('products.update', $product), [
            'product_name' => 'Crispy Chicken',
            'price' => 99.50,
            'status' => 'Active',
            'image' => UploadedFile::fake()->image('new.png'),
        ]);

        $product->refresh();
        $response->assertRedirect(route('products.index'));
        $this->assertNotSame($oldImagePath, $product->image_path);
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($product->image_path);

        $replacementPath = $product->image_path;
        $this->actingAs($manager)->put(route('products.update', $product), [
            'product_name' => 'Crispy Chicken',
            'price' => 99.50,
            'status' => 'Active',
            'remove_image' => 1,
        ])->assertRedirect(route('products.index'));

        $this->assertNull($product->fresh()->image_path);
        Storage::disk('public')->assertMissing($replacementPath);
    }

    public function test_manager_cannot_upload_a_non_image_as_product_photo(): void
    {
        Storage::fake('public');
        $manager = $this->createManager();

        $response = $this->actingAs($manager)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'product_name' => 'Crispy Chicken',
                'price' => 99.50,
                'status' => 'Active',
                'image' => UploadedFile::fake()->create('not-an-image.txt', 10, 'text/plain'),
            ]);

        $response->assertRedirect(route('products.create'));
        $response->assertSessionHasErrors('image');
        $this->assertDatabaseCount('products', 0);
        Storage::disk('public')->assertDirectoryEmpty('products');
    }

    public function test_pos_menu_displays_product_photo_and_placeholder_for_products_without_one(): void
    {
        Storage::fake('public');
        $cashier = $this->createCashier();
        $imagePath = UploadedFile::fake()->image('chicken.png')->store('products', 'public');
        Product::create([
            'product_name' => 'Crispy Chicken',
            'image_path' => $imagePath,
            'price' => 99.50,
            'status' => 'Active',
        ]);
        Product::create([
            'product_name' => 'Plain Rice',
            'price' => 20,
            'status' => 'Active',
        ]);

        $response = $this->actingAs($cashier)->get(route('orders.create'));

        $response->assertOk();
        $response->assertSee('storage/'.$imagePath, false);
        $response->assertSee('Crispy Chicken');
        $response->assertSee('Plain Rice');
        $response->assertSee('🍗');
    }

    private function createManager(): User
    {
        return $this->createUserWithRole('Manager', 'manager@example.com');
    }

    private function createCashier(): User
    {
        return $this->createUserWithRole('Cashier', 'cashier@example.com');
    }

    private function createUserWithRole(string $roleName, string $email): User
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
