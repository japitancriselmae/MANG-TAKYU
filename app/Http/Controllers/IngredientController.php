<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\InventoryCategory;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IngredientController extends Controller
{
    public function index()
    {
        return redirect()->route('inventories.index', ['tab' => 'stock']);
    }

    public function create()
    {
        $categories = InventoryCategory::orderBy('name')->get();

        return view('ingredients.create', compact('categories'));
    }

    public function store(Request $request, ActivityLogger $activityLogger)
    {
        $validated = $request->validate([
            'ingredient_name' => [
                'required',
                'string',
                'max:255',
                'unique:ingredients,ingredient_name',
            ],
            'inventory_category_id' => [
                'nullable',
                'exists:inventory_categories,id',
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                'unique:ingredients,sku',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'unit' => [
                'required',
                'string',
                'max:50',
            ],
            'minimum_stock' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
            ],
            'purchase_price' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
            ],
            'status' => [
                'required',
                'in:Active,Inactive',
            ],
        ]);

        DB::transaction(function () use ($validated, $activityLogger): void {
            $ingredient = Ingredient::create($validated);

            $activityLogger->record(
                action: 'ingredient.created',
                module: 'inventory',
                description: 'Inventory ingredient created.',
                subject: $ingredient,
                newValues: $this->auditValues($ingredient),
            );
        });

        return redirect()
            ->route('inventories.index', ['tab' => 'stock'])
            ->with('success', 'Ingredient created successfully.');
    }

    public function show(Ingredient $ingredient)
    {
        $ingredient->load([
            'inventories',
            'stockMovements',
            'productIngredients',
        ]);

        return view('ingredients.show', compact('ingredient'));
    }

    public function edit(Ingredient $ingredient)
    {
        $categories = InventoryCategory::orderBy('name')->get();

        return view('ingredients.edit', compact('ingredient', 'categories'));
    }

    public function update(Request $request, Ingredient $ingredient, ActivityLogger $activityLogger)
    {
        $validated = $request->validate([
            'ingredient_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ingredients', 'ingredient_name')
                    ->ignore($ingredient->ingredient_id, 'ingredient_id'),
            ],
            'inventory_category_id' => [
                'nullable',
                'exists:inventory_categories,id',
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('ingredients', 'sku')
                    ->ignore($ingredient->ingredient_id, 'ingredient_id'),
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'unit' => [
                'required',
                'string',
                'max:50',
            ],
            'minimum_stock' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
            ],
            'purchase_price' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
            ],
            'status' => [
                'required',
                'in:Active,Inactive',
            ],
        ]);

        DB::transaction(function () use ($validated, $ingredient, $activityLogger): void {
            $ingredient = Ingredient::query()
                ->whereKey($ingredient->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $oldValues = $this->auditValues($ingredient);
            $ingredient->update($validated);

            $activityLogger->record(
                action: 'ingredient.updated',
                module: 'inventory',
                description: 'Inventory ingredient information updated.',
                subject: $ingredient,
                oldValues: $oldValues,
                newValues: $this->auditValues($ingredient),
            );
        });

        return redirect()
            ->route('inventories.index', ['tab' => 'stock'])
            ->with('success', 'Ingredient updated successfully.');
    }

    public function destroy(Ingredient $ingredient, ActivityLogger $activityLogger)
    {
        if ($ingredient->inventories()->exists()) {
            return back()->withErrors([
                'ingredient' => 'This ingredient cannot be deleted because it has inventory records.',
            ]);
        }

        if ($ingredient->stockMovements()->exists()) {
            return back()->withErrors([
                'ingredient' => 'This ingredient cannot be deleted because it has stock movement records.',
            ]);
        }

        if ($ingredient->productIngredients()->exists()) {
            return back()->withErrors([
                'ingredient' => 'This ingredient cannot be deleted because it is already used in a product recipe.',
            ]);
        }

        DB::transaction(function () use ($ingredient, $activityLogger): void {
            $oldValues = $this->auditValues($ingredient);
            $ingredient->delete();

            $activityLogger->record(
                action: 'ingredient.deleted',
                module: 'inventory',
                description: 'Inventory ingredient deleted.',
                subject: $ingredient,
                oldValues: $oldValues,
            );
        });

        return redirect()
            ->route('inventories.index', ['tab' => 'stock'])
            ->with('success', 'Ingredient deleted successfully.');
    }

    /**
     * @return array{ ingredient_name: string, sku: string|null, category_id: int|null, unit: string, minimum_stock: string, purchase_price: string, status: string }
     */
    private function auditValues(Ingredient $ingredient): array
    {
        return [
            'ingredient_name' => $ingredient->ingredient_name,
            'sku' => $ingredient->sku,
            'category_id' => $ingredient->inventory_category_id,
            'unit' => $ingredient->unit,
            'minimum_stock' => (string) $ingredient->minimum_stock,
            'purchase_price' => (string) $ingredient->purchase_price,
            'status' => $ingredient->status,
        ];
    }
}
