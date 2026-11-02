<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductIngredientController extends Controller
{
    public function index()
    {
        return redirect()
            ->route('inventories.index', ['tab' => 'recipes'])
            ->with('success', 'Recipe management is now under the Inventory workspace.');
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

    public function store(Request $request, ActivityLogger $activityLogger)
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
                    'ingredient_id' => 'This ingredient is already part of this product recipe.',
                ])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $activityLogger): void {
            $productIngredient = ProductIngredient::create($validated);

            $activityLogger->record(
                action: 'product.recipe_item_added',
                module: 'products',
                description: 'Ingredient added to product recipe.',
                subject: $productIngredient,
                newValues: $this->auditValues($productIngredient),
            );
        });

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
        ProductIngredient $productIngredient,
        ActivityLogger $activityLogger,
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
                    'ingredient_id' => 'This ingredient is already part of this product recipe.',
                ])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $productIngredient, $activityLogger): void {
            $productIngredient = ProductIngredient::query()
                ->whereKey($productIngredient->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $oldValues = $this->auditValues($productIngredient);
            $productIngredient->update($validated);

            $activityLogger->record(
                action: 'product.recipe_item_updated',
                module: 'products',
                description: 'Product recipe ingredient updated.',
                subject: $productIngredient,
                oldValues: $oldValues,
                newValues: $this->auditValues($productIngredient),
            );
        });

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
        ProductIngredient $productIngredient,
        ActivityLogger $activityLogger,
    ) {
        $productId = $productIngredient->product_id;

        DB::transaction(function () use ($productIngredient, $activityLogger): void {
            $oldValues = $this->auditValues($productIngredient);
            $productIngredient->delete();

            $activityLogger->record(
                action: 'product.recipe_item_removed',
                module: 'products',
                description: 'Ingredient removed from product recipe.',
                subject: $productIngredient,
                oldValues: $oldValues,
            );
        });

        return redirect()
            ->route('products.show', $productId)
            ->with(
                'success',
                'Ingredient removed from product recipe successfully.'
            );
    }

    /**
     * @return array{ product_id: int, ingredient_id: int, quantity_required: string }
     */
    private function auditValues(ProductIngredient $productIngredient): array
    {
        return [
            'product_id' => $productIngredient->product_id,
            'ingredient_id' => $productIngredient->ingredient_id,
            'quantity_required' => (string) $productIngredient->quantity_required,
        ];
    }
}
