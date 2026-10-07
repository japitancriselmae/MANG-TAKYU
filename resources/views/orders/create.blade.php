@extends('layouts.app')

@section('title', 'New Order')
@section('page-title', 'New Order')
@section('page-subtitle', 'Create a customer order — items, type, and fresh chicken option.')

@section('content')

<form method="POST" action="{{ route('orders.store') }}" id="orderForm">
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- PRODUCTS --}}
        <div class="xl:col-span-2">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-800">Menu</h3>
                        <p class="text-xs text-slate-500">Tap + to add items to the order</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ $products->count() }} product(s)</span>
                </div>

                @if ($products->count() === 0)
                    <div class="px-6 py-16 text-center">
                        <div class="text-4xl mb-3">🍗</div>
                        <p class="text-slate-500">No active products available.</p>
                        <p class="text-xs text-slate-400 mt-2">Add products first from the Products page.</p>
                    </div>
                @else
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($products as $index => $product)
                            <div class="product-card border border-slate-200 rounded-xl p-4 hover:border-[#E31E24]/40 hover:shadow-sm transition"
                                 data-product-id="{{ $product->product_id }}"
                                 data-price="{{ $product->price }}">

                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h4 class="font-bold text-slate-800">{{ $product->product_name }}</h4>
                                    <div class="text-lg font-bold text-[#E31E24] whitespace-nowrap">
                                        ₱{{ number_format((float) $product->price, 2) }}
                                    </div>
                                </div>

                                @if ($product->productIngredients->count() > 0)
                                    <div class="mb-3 text-xs text-slate-500">
                                        <span class="font-semibold text-slate-600">Recipe:</span>
                                        @foreach ($product->productIngredients as $ri)
                                            <span>{{ $ri->ingredient?->ingredient_name ?? '?' }} ({{ $ri->quantity_required }}){{ !$loop->last ? ',' : '' }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="flex items-center gap-3 mt-4">
                                    <button type="button" class="minus-btn w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-lg transition"
                                            data-index="{{ $index }}">−</button>

                                    <input type="number" class="quantity-input w-20 h-10 text-center border border-slate-200 rounded-lg font-semibold text-slate-800 focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20"
                                           id="quantity-{{ $index }}" value="0" min="0" step="1" data-index="{{ $index }}">

                                    <button type="button" class="plus-btn w-10 h-10 rounded-lg bg-[#E31E24] hover:bg-red-700 text-white font-bold text-lg transition"
                                            data-index="{{ $index }}">+</button>
                                </div>

                                <input type="hidden" class="product-id-input"
                                       id="product-id-{{ $index }}"
                                       name="items[{{ $index }}][product_id]"
                                       value="{{ $product->product_id }}" disabled>

                                <input type="hidden" class="quantity-hidden-input"
                                       id="quantity-hidden-{{ $index }}"
                                       name="items[{{ $index }}][quantity]"
                                       value="0" disabled>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- SUMMARY --}}
        <div class="xl:col-span-1">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sticky top-24">

                <h3 class="font-bold text-slate-800 mb-5">Order Summary</h3>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Order Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="relative">
                            <input type="radio" name="order_type" value="Dine-in" required
                                   class="peer sr-only" {{ old('order_type') === 'Dine-in' ? 'checked' : '' }}>
                            <div class="px-4 py-3 text-center rounded-xl border-2 border-slate-200 cursor-pointer
                                        peer-checked:border-[#E31E24] peer-checked:bg-red-50 peer-checked:text-[#E31E24]
                                        font-semibold text-slate-600 transition">
                                🍽️ Dine-in
                            </div>
                        </label>
                        <label class="relative">
                            <input type="radio" name="order_type" value="Take-out" required
                                   class="peer sr-only" {{ old('order_type') === 'Take-out' ? 'checked' : '' }}>
                            <div class="px-4 py-3 text-center rounded-xl border-2 border-slate-200 cursor-pointer
                                        peer-checked:border-[#E31E24] peer-checked:bg-red-50 peer-checked:text-[#E31E24]
                                        font-semibold text-slate-600 transition">
                                🥡 Take-out
                            </div>
                        </label>
                    </div>
                </div>

                <label class="block mb-5 cursor-pointer">
                    <div class="flex items-start gap-3 p-4 rounded-xl border-2 border-slate-200 has-[:checked]:border-[#F5A623] has-[:checked]:bg-amber-50 transition">
                        <input type="checkbox" name="is_fresh_chicken" value="1"
                               {{ old('is_fresh_chicken') ? 'checked' : '' }}
                               class="mt-0.5 w-5 h-5 rounded border-slate-300 text-[#F5A623] focus:ring-[#F5A623]">
                        <div>
                            <div class="font-semibold text-slate-800">🍗 Fresh Chicken Order</div>
                            <div class="text-xs text-slate-500 mt-0.5">Starts a 12-minute preparation timer when confirmed.</div>
                        </div>
                    </div>
                </label>

                <div class="mb-6 p-5 rounded-xl bg-gradient-to-br from-[#0B0B0B] to-[#1a1a1a] text-white">
                    <div class="text-xs uppercase tracking-wider text-white/60 font-semibold mb-1">Order Total</div>
                    <div class="text-4xl font-bold"><span id="totalAmount">₱0.00</span></div>
                </div>

                <button type="submit"
                        class="w-full py-3.5 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-bold
                               tracking-wide shadow-lg shadow-red-500/20 transition disabled:opacity-50">
                    Create Order
                </button>

                <a href="{{ route('orders.index') }}"
                   class="block text-center mt-3 py-3 rounded-xl border border-slate-200 text-slate-600
                          font-semibold hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
function updateTotal() {
    let total = 0;
    document.querySelectorAll('.product-card').forEach(card => {
        const price = parseFloat(card.dataset.price) || 0;
        const qty   = parseInt(card.querySelector('.quantity-input').value) || 0;
        total += price * qty;
    });
    document.getElementById('totalAmount').textContent = '₱' + total.toFixed(2);
}

function updateHiddenInputs(card) {
    const qty        = parseInt(card.querySelector('.quantity-input').value) || 0;
    const pidInput   = card.querySelector('.product-id-input');
    const qtyInput   = card.querySelector('.quantity-hidden-input');
    qtyInput.value   = qty;
    pidInput.disabled = qty === 0;
    qtyInput.disabled = qty === 0;
}

document.querySelectorAll('.plus-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const i = this.dataset.index;
        const input = document.getElementById('quantity-' + i);
        input.value = (parseInt(input.value) || 0) + 1;
        updateHiddenInputs(this.closest('.product-card'));
        updateTotal();
    });
});

document.querySelectorAll('.minus-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const i = this.dataset.index;
        const input = document.getElementById('quantity-' + i);
        const v = parseInt(input.value) || 0;
        input.value = v > 0 ? v - 1 : 0;
        updateHiddenInputs(this.closest('.product-card'));
        updateTotal();
    });
});

document.querySelectorAll('.quantity-input').forEach(input => {
    input.addEventListener('input', function () {
        let v = parseInt(this.value) || 0;
        if (v < 0) v = 0;
        this.value = v;
        updateHiddenInputs(this.closest('.product-card'));
        updateTotal();
    });
});

document.getElementById('orderForm').addEventListener('submit', function (e) {
    let hasItems = false;
    document.querySelectorAll('.product-card').forEach(card => {
        const qty = parseInt(card.querySelector('.quantity-input').value) || 0;
        if (qty > 0) { hasItems = true; updateHiddenInputs(card); }
    });
    if (!hasItems) {
        e.preventDefault();
        alert('Please select at least one product.');
    }
});

document.querySelectorAll('.product-card').forEach(updateHiddenInputs);
updateTotal();
</script>
@endpush

@endsection