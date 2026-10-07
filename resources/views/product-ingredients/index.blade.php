@extends('layouts.app')

@section('title', 'Product Recipes')
@section('page-title', 'Product Recipes')
@section('page-subtitle', 'Manage ingredients required for each product.')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Recipe List</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $productIngredients->count() }} recipe(s)</p>
    </div>
    <a href="{{ route('product-ingredients.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
              text-white font-semibold shadow-lg shadow-red-500/20 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add Recipe Ingredient
    </a>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    @if ($productIngredients->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">ID</th>
                        <th class="px-6 py-3 font-semibold">Product</th>
                        <th class="px-6 py-3 font-semibold">Ingredient</th>
                        <th class="px-6 py-3 font-semibold">Quantity Required</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($productIngredients as $recipe)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                #{{ str_pad($recipe->product_ingredient_id, 3, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                {{ $recipe->product->product_name ?? 'Unknown Product' }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $recipe->ingredient->ingredient_name ?? 'Unknown Ingredient' }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ number_format($recipe->quantity_required, 2) }}
                                {{ $recipe->ingredient->unit ?? '' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('product-ingredients.show', $recipe) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        View
                                    </a>
                                    <a href="{{ route('product-ingredients.edit', $recipe) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('product-ingredients.destroy', $recipe) }}" method="POST"
                                          onsubmit="return confirm('Remove this ingredient?');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-20 text-center">
            <div class="text-5xl mb-3">📖</div>
            <p class="font-bold text-slate-700">No recipes yet</p>
            <p class="text-sm text-slate-500 mt-1 mb-6">Add ingredients to a product to create a recipe.</p>
            <a href="{{ route('product-ingredients.create') }}"
               class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                Add First Recipe
            </a>
        </div>
    @endif
</div>

@endsection