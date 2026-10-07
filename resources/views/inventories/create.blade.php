@extends('layouts.app')

@section('title', 'Add Inventory')
@section('page-title', 'Add Inventory')
@section('page-subtitle', 'Create a new inventory record.')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('inventories.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="ingredient_id" class="block text-sm font-semibold text-slate-700 mb-2">
                    Ingredient
                </label>
                <select name="ingredient_id" id="ingredient_id" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    <option value="">Select Ingredient</option>
                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->ingredient_id }}"
                            {{ old('ingredient_id') == $ingredient->ingredient_id ? 'selected' : '' }}>
                            {{ $ingredient->ingredient_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="quantity" class="block text-sm font-semibold text-slate-700 mb-2">
                    Current Quantity
                </label>
                <input type="number" name="quantity" id="quantity"
                       value="{{ old('quantity', 0) }}" min="0" step="0.01" required
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div>
                <label for="minimum_stock" class="block text-sm font-semibold text-slate-700 mb-2">
                    Minimum Stock Level
                </label>
                <input type="number" name="minimum_stock" id="minimum_stock"
                       value="{{ old('minimum_stock', 0) }}" min="0" step="0.01" required
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                <p class="text-xs text-slate-500 mt-2">
                    You'll get a low-stock notification when quantity reaches this level.
                </p>
            </div>

            <div>
                <label for="last_updated" class="block text-sm font-semibold text-slate-700 mb-2">
                    Last Updated
                </label>
                <input type="datetime-local" name="last_updated" id="last_updated"
                       value="{{ old('last_updated', now()->format('Y-m-d\TH:i')) }}" required
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div class="flex gap-3 pt-3">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                               shadow-lg shadow-red-500/20 transition">
                    Save Inventory
                </button>
                <a href="{{ route('inventories.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold
                          hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>

@endsection