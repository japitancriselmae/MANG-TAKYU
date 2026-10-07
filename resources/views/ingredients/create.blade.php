@extends('layouts.app')

@section('title', 'Add Ingredient')
@section('page-title', 'Add Ingredient')
@section('page-subtitle', 'Add a new ingredient to the inventory system.')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('ingredients.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="ingredient_name" class="block text-sm font-semibold text-slate-700 mb-2">
                    Ingredient Name
                </label>
                <input type="text" name="ingredient_name" id="ingredient_name"
                       value="{{ old('ingredient_name') }}" required maxlength="255"
                       placeholder="Chicken Breast"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div>
                <label for="unit" class="block text-sm font-semibold text-slate-700 mb-2">
                    Unit
                </label>
                <input type="text" name="unit" id="unit" value="{{ old('unit') }}"
                       required maxlength="50" placeholder="kg, pcs, cups, liters"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div>
                <label for="minimum_stock" class="block text-sm font-semibold text-slate-700 mb-2">
                    Minimum Stock
                </label>
                <input type="number" name="minimum_stock" id="minimum_stock"
                       value="{{ old('minimum_stock') }}" min="0" step="0.01" required
                       placeholder="10.00"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                <p class="text-xs text-slate-500 mt-2">
                    The system will alert you when stock drops to or below this level.
                </p>
            </div>

            <div>
                <label for="status" class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                <select name="status" id="status" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    <option value="Active"   {{ old('status', 'Active') === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ old('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="flex gap-3 pt-3">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                               shadow-lg shadow-red-500/20 transition">
                    Save Ingredient
                </button>
                <a href="{{ route('ingredients.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold
                          hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>

@endsection