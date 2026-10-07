<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index()
    {
        $stockMovements = StockMovement::with('ingredient')
            ->orderByDesc('movement_date')
            ->orderByDesc('movement_id')
            ->get();

        return view(
            'stock-movements.index',
            compact('stockMovements')
        );
    }

    public function create()
    {
        $ingredients = Ingredient::orderBy('ingredient_name')->get();

        return view(
            'stock-movements.create',
            compact('ingredients')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ingredient_id' => [
                'required',
                'exists:ingredients,ingredient_id',
            ],

            'movement_type' => [
                'required',
                'string',
                'max:255',
            ],

            'quantity' => [
                'required',
                'numeric',
            ],

            'movement_date' => [
                'required',
                'date',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        StockMovement::create($validated);

        return redirect()
            ->route('stock-movements.index')
            ->with(
                'success',
                'Stock movement created successfully.'
            );
    }

    public function show(StockMovement $stockMovement)
    {
        $stockMovement->load('ingredient');

        return view(
            'stock-movements.show',
            compact('stockMovement')
        );
    }

    public function edit(StockMovement $stockMovement)
    {
        $ingredients = Ingredient::orderBy('ingredient_name')->get();

        $stockMovement->load('ingredient');

        return view(
            'stock-movements.edit',
            compact(
                'stockMovement',
                'ingredients'
            )
        );
    }

    public function update(
        Request $request,
        StockMovement $stockMovement
    ) {
        $validated = $request->validate([
            'ingredient_id' => [
                'required',
                'exists:ingredients,ingredient_id',
            ],

            'movement_type' => [
                'required',
                'string',
                'max:255',
            ],

            'quantity' => [
                'required',
                'numeric',
            ],

            'movement_date' => [
                'required',
                'date',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $stockMovement->update($validated);

        return redirect()
            ->route('stock-movements.index')
            ->with(
                'success',
                'Stock movement updated successfully.'
            );
    }

    public function destroy(StockMovement $stockMovement)
    {
        $stockMovement->delete();

        return redirect()
            ->route('stock-movements.index')
            ->with(
                'success',
                'Stock movement deleted successfully.'
            );
    }
}
