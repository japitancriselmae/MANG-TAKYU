<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $inventories = Inventory::with('ingredient')
            ->orderBy('quantity')
            ->get();

        return view(
            'inventories.index',
            compact('inventories')
        );
    }

    public function create()
    {
        $ingredients = Ingredient::orderBy('ingredient_name')->get();

        return view(
            'inventories.create',
            compact('ingredients')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ingredient_id' => [
                'required',
                'exists:ingredients,ingredient_id',
                'unique:inventories,ingredient_id',
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0',
            ],

            'minimum_stock' => [
                'required',
                'numeric',
                'min:0',
            ],

            'last_updated' => [
                'required',
                'date',
            ],
        ]);

        Inventory::create($validated);

        return redirect()
            ->route('inventories.index')
            ->with(
                'success',
                'Inventory created successfully.'
            );
    }

    public function show(Inventory $inventory)
    {
        $inventory->load('ingredient');

        return view(
            'inventories.show',
            compact('inventory')
        );
    }

    public function edit(Inventory $inventory)
    {
        $ingredients = Ingredient::orderBy('ingredient_name')->get();

        $inventory->load('ingredient');

        return view(
            'inventories.edit',
            compact(
                'inventory',
                'ingredients'
            )
        );
    }

    public function update(
        Request $request,
        Inventory $inventory
    ) {
        $validated = $request->validate([
            'ingredient_id' => [
                'required',
                'exists:ingredients,ingredient_id',
                'unique:inventories,ingredient_id,' .
                    $inventory->inventory_id . ',inventory_id',
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0',
            ],

            'minimum_stock' => [
                'required',
                'numeric',
                'min:0',
            ],

            'last_updated' => [
                'required',
                'date',
            ],
        ]);

        $inventory->update($validated);

        return redirect()
            ->route('inventories.index')
            ->with(
                'success',
                'Inventory updated successfully.'
            );
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        return redirect()
            ->route('inventories.index')
            ->with(
                'success',
                'Inventory deleted successfully.'
            );
    }
}
