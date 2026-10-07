@extends('layouts.app')

@section('title', 'Add Recipe Ingredient')
@section('page-title', 'Add Recipe Ingredient')
@section('page-subtitle', 'Define how much of an ingredient is required for one product.')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('product-ingredients.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="product_id" class="block text-sm font-semibold text-slate-700 mb-2">Product</label>
                <select name="product_id" id="product_id" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    <option value="">Select Product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->product_id }}"
                            {{ old('product_id', request('product_id')) == $product->product_id ? 'selected' : '' }}>
                            {{ $product->product_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="ingredient_id" class="block text-sm font-semibold text-slate-700 mb-2">Ingredient</label>
                <select name="ingredient_id" id="ingredient_id" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    <option value="">Select Ingredient</option>
                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->ingredient_id }}"
                            {{ old('ingredient_id') == $ingredient->ingredient_id ? 'selected' : '' }}>
                            {{ $ingredient->ingredient_name }} ({{ $ingredient->unit }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="quantity_required" class="block text-sm font-semibold text-slate-700 mb-2">
                    Quantity Required
                </label>
                <input type="number" name="quantity_required" id="quantity_required"
                       value="{{ old('quantity_required') }}" min="0.01" step="0.01" required
                       placeholder="1.00"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                <p class="text-xs text-slate-500 mt-2">
                    Amount needed to prepare one unit of the product.
                </p>
            </div>

            <div class="flex gap-3 pt-3">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                               shadow-lg shadow-red-500/20 transition">
                    Add to Recipe
                </button>
                <a href="{{ route('products.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold
                          hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>

@endsection