@extends('layouts.app')

@section('title', 'Edit Order #' . $order->order_id)
@section('page-title', 'Edit Order #' . $order->order_id)
@section('page-subtitle', 'Update the products and quantities for this order.')

@section('content')

<form action="{{ route('orders.update', $order) }}" method="POST" id="orderForm">
    @csrf @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- PRODUCTS --}}
        <div class="xl:col-span-2">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800">Order Items</h3>
                    <p class="text-xs text-slate-500">Change products and quantities below</p>
                </div>

                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($products as $product)
                        @php
                            $existing = $order->orderItems->firstWhere('product_id', $product->product_id);
                        @endphp

                        <div class="border border-slate-200 rounded-xl p-4 hover:border-[#E31E24]/40 transition">
                            <h4 class="font-bold text-slate-800">{{ $product->product_name }}</h4>
                            <div class="text-lg font-bold text-[#E31E24] mb-3">
                                ₱{{ number_format($product->price, 2) }}
                            </div>

                            @if ($product->productIngredients->count() > 0)
                                <div class="mb-3 text-xs text-slate-500">
                                    @foreach ($product->productIngredients as $ri)
                                        <div>{{ $ri->ingredient?->ingredient_name ?? '?' }} ({{ $ri->quantity_required }})</div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex items-center gap-3">
                                <button type="button" onclick="changeQty({{ $product->product_id }}, -1)"
                                        class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-lg">
                                    −
                                </button>

                                <input type="number" id="quantity-{{ $product->product_id }}"
                                       name="items[{{ $product->product_id }}][quantity]"
                                       value="{{ old('items.'.$product->product_id.'.quantity', $existing?->quantity ?? 0) }}"
                                       min="0"
                                       onchange="normalizeQty({{ $product->product_id }})"
                                       class="w-20 h-10 text-center border border-slate-200 rounded-lg font-semibold text-slate-800 focus:outline-none focus:border-[#E31E24]">

                                <button type="button" onclick="changeQty({{ $product->product_id }}, 1)"
                                        class="w-10 h-10 rounded-lg bg-[#E31E24] hover:bg-red-700 text-white font-bold text-lg">
                                    +
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- SUMMARY --}}
        <div class="xl:col-span-1">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sticky top-24">

                <h3 class="font-bold text-slate-800 mb-5">Order Summary</h3>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Order Type</label>
                    <select name="order_type" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20">
                        <option value="Dine-in"  {{ old('order_type', $order->order_type) === 'Dine-in'  ? 'selected' : '' }}>Dine-in</option>
                        <option value="Take-out" {{ old('order_type', $order->order_type) === 'Take-out' ? 'selected' : '' }}>Take-out</option>
                    </select>
                </div>

                <label class="block mb-5 cursor-pointer">
                    <div class="flex items-start gap-3 p-4 rounded-xl border-2 border-slate-200 has-[:checked]:border-[#F5A623] has-[:checked]:bg-amber-50 transition">
                        <input type="checkbox" name="is_fresh_chicken" value="1"
                               {{ old('is_fresh_chicken', $order->is_fresh_chicken) ? 'checked' : '' }}
                               class="mt-0.5 w-5 h-5 rounded border-slate-300 text-[#F5A623] focus:ring-[#F5A623]">
                        <div>
                            <div class="font-semibold text-slate-800">🍗 Fresh Chicken Order</div>
                            <div class="text-xs text-slate-500 mt-0.5">12-minute prep timer</div>
                        </div>
                    </div>
                </label>

                <div class="mb-5 p-4 rounded-xl bg-slate-50">
                    <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Current Status</div>
                    <div class="font-bold text-slate-800">{{ $order->status }}</div>
                </div>

                <div class="mb-6 p-5 rounded-xl bg-gradient-to-br from-[#0B0B0B] to-[#1a1a1a] text-white">
                    <div class="text-xs uppercase tracking-wider text-white/60 font-semibold mb-1">New Total</div>
                    <div class="text-4xl font-bold"><span id="totalAmount">₱0.00</span></div>
                </div>

                <button type="submit"
                        class="w-full py-3.5 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-bold shadow-lg shadow-red-500/20 transition">
                    Update Order
                </button>

                <a href="{{ route('orders.show', $order) }}"
                   class="block text-center mt-3 py-3 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition">
                    Cancel
                </a>

                <div class="mt-5 p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800">
                    <strong>Note:</strong> Only pending orders can be edited.
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
const products = @json($products->mapWithKeys(fn($p) => [$p->product_id => ['price' => (float)$p->price]]));

function changeQty(id, delta) {
    const input = document.getElementById('quantity-' + id);
    let v = (parseInt(input.value) || 0) + delta;
    if (v < 0) v = 0;
    input.value = v;
    updateTotal();
}

function normalizeQty(id) {
    const input = document.getElementById('quantity-' + id);
    let v = parseInt(input.value) || 0;
    if (v < 0) v = 0;
    input.value = v;
    updateTotal();
}

function updateTotal() {
    let total = 0;
    Object.keys(products).forEach(id => {
        const input = document.getElementById('quantity-' + id);
        if (!input) return;
        total += (parseInt(input.value) || 0) * products[id].price;
    });
    document.getElementById('totalAmount').textContent = '₱' + total.toFixed(2);
}

document.getElementById('orderForm').addEventListener('submit', function (e) {
    let has = false;
    Object.keys(products).forEach(id => {
        const input = document.getElementById('quantity-' + id);
        if (input && (parseInt(input.value) || 0) > 0) has = true;
    });
    if (!has) { e.preventDefault(); alert('Please select at least one product.'); }
});

updateTotal();
</script>
@endpush

@endsection