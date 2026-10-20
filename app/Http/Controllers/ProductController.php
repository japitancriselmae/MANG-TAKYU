<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('productIngredients.ingredient')
            ->orderBy('product_name')
            ->get();

        $productIngredients = ProductIngredient::with(['product', 'ingredient'])
            ->orderBy('product_id')
            ->orderBy('ingredient_id')
            ->get();

        $activeTab = $request->query('tab') === 'recipes' ? 'recipes' : 'products';

        return view(
            'products.index',
            compact('products', 'productIngredients', 'activeTab')
        );
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name' => [
                'required',
                'string',
                'max:255',
                'unique:products,product_name',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'status' => [
                'required',
                'in:Active,Inactive',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $productData = collect($validated)->except('image')->all();
        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');

            if (! $imagePath) {
                throw new RuntimeException('The product image could not be saved.');
            }

            $productData['image_path'] = $imagePath;
        }

        try {
            Product::create($productData);
        } catch (Throwable $exception) {
            if ($imagePath && ! Storage::disk('public')->delete($imagePath)) {
                throw new RuntimeException('The product was not created and its uploaded image could not be removed.', previous: $exception);
            }

            throw $exception;
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $product->load([
            'productIngredients.ingredient',
            'orderItems',
        ]);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'product_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'product_name')
                    ->ignore($product->product_id, 'product_id'),
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'status' => [
                'required',
                'in:Active,Inactive',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'remove_image' => [
                'nullable',
                'boolean',
            ],
        ]);

        $productData = collect($validated)->except(['image', 'remove_image'])->all();
        $oldImagePath = $product->image_path;
        $newImagePath = null;

        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('products', 'public');

            if (! $newImagePath) {
                throw new RuntimeException('The product image could not be saved.');
            }

            $productData['image_path'] = $newImagePath;
        } elseif ($request->boolean('remove_image')) {
            $productData['image_path'] = null;
        }

        try {
            $product->update($productData);
        } catch (Throwable $exception) {
            if ($newImagePath && ! Storage::disk('public')->delete($newImagePath)) {
                throw new RuntimeException('The product was not updated and its uploaded image could not be removed.', previous: $exception);
            }

            throw $exception;
        }

        if (
            $oldImagePath
            && $oldImagePath !== $product->image_path
            && ! Storage::disk('public')->delete($oldImagePath)
        ) {
            throw new RuntimeException('The product was updated but its previous image could not be removed.');
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->orderItems()->exists()) {
            return back()->withErrors([
                'product' => 'This product cannot be deleted because it has order records.',
            ]);
        }

        if ($product->productIngredients()->exists()) {
            return back()->withErrors([
                'product' => 'This product cannot be deleted because it has recipe ingredients. Remove the recipe ingredients first.',
            ]);
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
