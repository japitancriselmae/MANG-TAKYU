<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('productIngredients.ingredient')
            ->orderBy('product_name')
            ->get();

        return view('products.index', compact('products'));
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
        ]);

        Product::create($validated);

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
        ]);

        $product->update($validated);

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
