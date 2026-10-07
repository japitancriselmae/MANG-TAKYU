<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Http\Request;

class ProductIngredientController extends Controller
{
    public function index()
    {
        $productIngredients = ProductIngredient::with([
            'product',
            'ingredient',
        ])
            ->orderBy('product_id')
            ->orderBy('ingredient_id')
            ->get();

        return view(
            'product-ingredients.index',
            compact('productIngredients')
        );
    }

    public function create(Request $request)
    {
        $products = Product::where('status', 'Active')
            ->orderBy('product_name')
            ->get();

        $ingredients = Ingredient::where('status', 'Active')
            ->orderBy('ingredient_name')
            ->get();

        return view(
            'product-ingredients.create',
            compact(
                'products',
                'ingredients'
            )
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'exists:products,product_id',
            ],
            'ingredient_id' => [
                'required',
                'exists:ingredients,ingredient_id',
            ],
            'quantity_required' => [
                'required',
                'numeric',
                'min:0.01',
            ],
        ]);

        $duplicate = ProductIngredient::where(
            'product_id',
            $validated['product_id']
        )
            ->where(
                'ingredient_id',
                $validated['ingredient_id']
            )
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors([
                    'ingredient_id' =>
                    'This ingredient is already part of this product recipe.',
                ])
                ->withInput();
        }

        ProductIngredient::create($validated);

        return redirect()
            ->route(
                'products.show',
                $validated['product_id']
            )
            ->with(
                'success',
                'Ingredient added to product recipe successfully.'
            );
    }

    public function show(ProductIngredient $productIngredient)
    {
        $productIngredient->load([
            'product',
            'ingredient',
        ]);

        return view(
            'product-ingredients.show',
            compact('productIngredient')
        );
    }

    public function edit(ProductIngredient $productIngredient)
    {
        $products = Product::where('status', 'Active')
            ->orderBy('product_name')
            ->get();

        $ingredients = Ingredient::where('status', 'Active')
            ->orderBy('ingredient_name')
            ->get();

        return view(
            'product-ingredients.edit',
            compact(
                'productIngredient',
                'products',
                'ingredients'
            )
        );
    }

    public function update(
        Request $request,
        ProductIngredient $productIngredient
    ) {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'exists:products,product_id',
            ],
            'ingredient_id' => [
                'required',
                'exists:ingredients,ingredient_id',
            ],
            'quantity_required' => [
                'required',
                'numeric',
                'min:0.01',
            ],
        ]);

        $duplicate = ProductIngredient::where(
            'product_id',
            $validated['product_id']
        )
            ->where(
                'ingredient_id',
                $validated['ingredient_id']
            )
            ->where(
                'product_ingredient_id',
                '!=',
                $productIngredient->product_ingredient_id
            )
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors([
                    'ingredient_id' =>
                    'This ingredient is already part of this product recipe.',
                ])
                ->withInput();
        }

        $productIngredient->update($validated);

        return redirect()
            ->route(
                'products.show',
                $validated['product_id']
            )
            ->with(
                'success',
                'Product recipe ingredient updated successfully.'
            );
    }

    public function destroy(
        ProductIngredient $productIngredient
    ) {
        $productId = $productIngredient->product_id;

        $productIngredient->delete();

        return redirect()
            ->route('products.show', $productId)
            ->with(
                'success',
                'Ingredient removed from product recipe successfully.'
            );
    }
}
