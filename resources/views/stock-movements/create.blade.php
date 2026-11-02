@extends('layouts.app')

@section('title', 'Record Stock Movement')
@section('page-title', 'Record Stock Movement')
@section('page-subtitle', 'Record a new ingredient stock movement.')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('stock-movements.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="ingredient_id" class="block text-sm font-semibold text-slate-700 mb-2">Ingredient</label>
                <select name="ingredient_id" id="ingredient_id" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    <option value="">Select Ingredient</option>
                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->ingredient_id }}"
                            {{ old('ingredient_id') == $ingredient->ingredient_id ? 'selected' : '' }}>
                                {{ $ingredient->ingredient_name }} · {{ number_format((float) ($ingredient->inventories->first()?->quantity ?? 0), 2) }} {{ $ingredient->unit }} available
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="movement_type" class="block text-sm font-semibold text-slate-700 mb-2">Movement Type</label>
                <select name="movement_type" id="movement_type" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    <option value="">Select Movement Type</option>
                    <option value="Stock In" @selected(old('movement_type') === 'Stock In')>Stock In</option>
                    <option value="Stock Out" @selected(old('movement_type') === 'Stock Out')>Stock Out</option>
                    <option value="Adjustment" @selected(old('movement_type') === 'Adjustment')>Physical count adjustment</option>
                </select>
            </div>

            <div>
                <label id="quantity-label" for="quantity" class="block text-sm font-semibold text-slate-700 mb-2">Quantity to add or remove</label>
                <input type="number" name="quantity" id="quantity"
                       value="{{ old('quantity') }}" min="0.01" step="0.01" required
                       placeholder="Enter a quantity"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <label for="remarks" class="block text-sm font-semibold text-slate-700">
                Notes <span class="font-normal text-slate-400">Optional</span>
                <textarea name="remarks" id="remarks" rows="3" maxlength="2000"
                          class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-[#E31E24] focus:ring-[#E31E24]/20">{{ old('remarks') }}</textarea>
            </label>

            <div>
                <label for="movement_date" class="block text-sm font-semibold text-slate-700 mb-2">Movement Date</label>
                <input type="datetime-local" name="movement_date" id="movement_date"
                       value="{{ old('movement_date', now()->format('Y-m-d\TH:i')) }}" required
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div>
                <label for="reference" class="block text-sm font-semibold text-slate-700 mb-2">
                    Reference (optional)
                </label>
                <input type="text" name="reference" id="reference"
                       value="{{ old('reference') }}" maxlength="255"
                       placeholder="Delivery #123"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div class="flex gap-3 pt-3">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                               shadow-lg shadow-red-500/20 transition">
                    Record Movement
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

@push('scripts')
    <script>
        (() => {
            const type = document.getElementById('movement_type');
            const quantity = document.getElementById('quantity');
            const label = document.getElementById('quantity-label');
            const updateQuantityInput = () => {
                if (type.value === 'Adjustment') {
                    label.textContent = 'Physically counted quantity';
                    quantity.min = '0';
                    quantity.placeholder = 'Enter the actual quantity on hand';
                } else {
                    label.textContent = 'Quantity to add or remove';
                    quantity.min = '0.01';
                    quantity.placeholder = 'Enter a quantity';
                }
            };
            type.addEventListener('change', updateQuantityInput);
            updateQuantityInput();
        })();
    </script>
@endpush

@endsection