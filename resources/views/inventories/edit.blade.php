@extends('layouts.app')

@section('title', 'Edit Inventory')
@section('page-title', 'Edit Inventory')
@section('page-subtitle', 'Update the inventory record.')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('inventories.update', $inventory) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label for="ingredient_id" class="block text-sm font-semibold text-slate-700 mb-2">
                    Stock item
                </label>
                <input type="hidden" name="ingredient_id" id="ingredient_id" value="{{ $inventory->ingredient_id }}">
                <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700">{{ $inventory->ingredient->ingredient_name }}</p>
            </div>

            <div>
                <label for="quantity" class="block text-sm font-semibold text-slate-700 mb-2">
                    Physically counted quantity
                </label>
                <input type="number" name="quantity" id="quantity"
                       value="{{ old('quantity', $inventory->quantity) }}" min="0" step="0.01" required
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                <p class="mt-2 text-xs text-slate-500">The stock difference will be recorded as an auditable adjustment.</p>
            </div>

            <div>
                <label for="minimum_stock" class="block text-sm font-semibold text-slate-700 mb-2">
                    Minimum Stock Level
                </label>
                <input type="number" name="minimum_stock" id="minimum_stock"
                       value="{{ old('minimum_stock', $inventory->minimum_stock) }}"
                       min="0" step="0.01" required
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div>
                <label for="last_updated" class="block text-sm font-semibold text-slate-700 mb-2">
                    Last Updated
                </label>
                <input type="datetime-local" name="last_updated" id="last_updated" required
                       value="{{ old('last_updated', $inventory->last_updated ? $inventory->last_updated->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div class="flex gap-3 pt-3">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                               shadow-lg shadow-red-500/20 transition">
                    Update Inventory
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