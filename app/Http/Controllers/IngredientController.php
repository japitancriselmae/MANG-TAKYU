<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IngredientController extends Controller
{
    public function index()
    {
        $ingredients = Ingredient::orderBy('ingredient_name')->get();

        return view('ingredients.index', compact('ingredients'));
    }

    public function create()
    {
        return view('ingredients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ingredient_name' => [
                'required',
                'string',
                'max:255',
                'unique:ingredients,ingredient_name',
            ],
            'unit' => [
                'required',
                'string',
                'max:50',
            ],
            'minimum_stock' => [
                'required',
                'numeric',
                'min:0',
            ],
            'status' => [
                'required',
                'in:Active,Inactive',
            ],
        ]);

        Ingredient::create($validated);

        return redirect()
            ->route('ingredients.index')
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
        return view('ingredients.edit', compact('ingredient'));
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'ingredient_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ingredients', 'ingredient_name')
                    ->ignore($ingredient->ingredient_id, 'ingredient_id'),
            ],
            'unit' => [
                'required',
                'string',
                'max:50',
            ],
            'minimum_stock' => [
                'required',
                'numeric',
                'min:0',
            ],
            'status' => [
                'required',
                'in:Active,Inactive',
            ],
        ]);

        $ingredient->update($validated);

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Ingredient updated successfully.');
    }

    public function destroy(Ingredient $ingredient)
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

        $ingredient->delete();

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Ingredient deleted successfully.');
    }
}
