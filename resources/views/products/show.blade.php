@extends('layouts.app')

@section('title', $product->product_name)
@section('page-title', $product->product_name)
@section('page-subtitle', 'Product details and recipe.')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Product Info</h2>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('products.edit', $product) }}"
           class="px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
            Edit Product
        </a>
        <a href="{{ route('product-ingredients.create', ['product_id' => $product->product_id]) }}"
           class="px-5 py-2.5 rounded-xl bg-[#F5A623] hover:bg-amber-500 text-white font-semibold shadow-lg shadow-amber-500/20 transition">
            + Add Ingredient
        </a>
    </div>
</div>

{{-- Info cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Product ID</div>
        <div class="text-2xl font-bold text-slate-800">
            #{{ str_pad($product->product_id, 3, '0', STR_PAD_LEFT) }}
        </div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Price</div>
        <div class="text-2xl font-bold text-[#E31E24]">
            ₱{{ number_format($product->price, 2) }}
        </div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Status</div>
        <div class="text-2xl font-bold {{ $product->status === 'Active' ? 'text-emerald-600' : 'text-slate-500' }}">
            {{ $product->status }}
        </div>
    </div>
</div>

{{-- Recipe --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
        <h3 class="font-bold text-slate-800">Product Recipe</h3>
        <p class="text-xs text-slate-500 mt-0.5">Ingredients required per unit.</p>
    </div>

    @if ($product->productIngredients->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">Ingredient</th>
                        <th class="px-6 py-3 font-semibold">Quantity Required</th>
                        <th class="px-6 py-3 font-semibold">Unit</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($product->productIngredients as $recipe)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                {{ $recipe->ingredient->ingredient_name ?? 'Unknown Ingredient' }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ number_format($recipe->quantity_required, 2) }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $recipe->ingredient->unit ?? '' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('product-ingredients.edit', $recipe) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('product-ingredients.destroy', $recipe) }}" method="POST"
                                          onsubmit="return confirm('Remove this ingredient?');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200 transition">
                                            Remove
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
        <div class="px-6 py-16 text-center">
            <div class="text-4xl mb-3">📖</div>
            <p class="font-semibold text-slate-700">No recipe ingredients yet</p>
            <p class="text-sm text-slate-500 mt-1 mb-6">Add ingredients to track stock deduction.</p>
            <a href="{{ route('product-ingredients.create', ['product_id' => $product->product_id]) }}"
               class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                Add First Ingredient
            </a>
        </div>
    @endif
</div>

@endsection