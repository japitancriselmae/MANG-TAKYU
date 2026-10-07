@extends('layouts.app')

@section('title', 'Edit Stock Movement')
@section('page-title', 'Edit Stock Movement')
@section('page-subtitle', 'Update the stock movement record.')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('stock-movements.update', $stockMovement) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label for="ingredient_id" class="block text-sm font-semibold text-slate-700 mb-2">Ingredient</label>
                <select name="ingredient_id" id="ingredient_id" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->ingredient_id }}"
                            {{ old('ingredient_id', $stockMovement->ingredient_id) == $ingredient->ingredient_id ? 'selected' : '' }}>
                            {{ $ingredient->ingredient_name }} ({{ $ingredient->unit }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="movement_type" class="block text-sm font-semibold text-slate-700 mb-2">Movement Type</label>
                <select name="movement_type" id="movement_type" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    <option value="Stock In"   {{ old('movement_type', $stockMovement->movement_type) === 'Stock In' ? 'selected' : '' }}>Stock In</option>
                    <option value="Stock Out"  {{ old('movement_type', $stockMovement->movement_type) === 'Stock Out' ? 'selected' : '' }}>Stock Out</option>
                    <option value="Adjustment" {{ old('movement_type', $stockMovement->movement_type) === 'Adjustment' ? 'selected' : '' }}>Adjustment</option>
                </select>
            </div>

            <div>
                <label for="quantity" class="block text-sm font-semibold text-slate-700 mb-2">Quantity</label>
                <input type="number" name="quantity" id="quantity"
                       value="{{ old('quantity', $stockMovement->quantity) }}"
                       min="0.01" step="0.01" required
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div>
                <label for="movement_date" class="block text-sm font-semibold text-slate-700 mb-2">Movement Date</label>
                <input type="datetime-local" name="movement_date" id="movement_date" required
                       value="{{ old('movement_date', \Carbon\Carbon::parse($stockMovement->movement_date)->format('Y-m-d\TH:i')) }}"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div>
                <label for="reference" class="block text-sm font-semibold text-slate-700 mb-2">
                    Reference (optional)
                </label>
                <input type="text" name="reference" id="reference"
                       value="{{ old('reference', $stockMovement->reference) }}" maxlength="255"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div class="flex gap-3 pt-3">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                               shadow-lg shadow-red-500/20 transition">
                    Update Movement
                </button>
                <a href="{{ route('stock-movements.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold
                          hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>

@endsection