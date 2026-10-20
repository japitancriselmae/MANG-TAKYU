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

                <div class="mb-5">
                <div class="mb-5 p-4 rounded-xl bg-slate-50 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Current status</span>
                        <span class="font-bold text-slate-800">{{ $order->status }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Previous total</span>
                        <span class="font-semibold text-slate-800">₱{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Already paid</span>
                        <span class="font-semibold text-slate-800">₱{{ number_format($order->payments->sum('amount_paid'), 2) }}</span>
                    </div>
                </div>

                <div class="mb-4 p-5 rounded-xl bg-gradient-to-br from-[#0B0B0B] to-[#1a1a1a] text-white">
                    <div class="text-xs uppercase tracking-wider text-white/60 font-semibold mb-1">New Order Total</div>
                    <div class="text-4xl font-bold"><span id="totalAmount">₱0.00</span></div>
                    <div id="balanceDueRow" class="hidden mt-3 pt-3 border-t border-white/20">
                        <div class="text-xs uppercase tracking-wider text-amber-300 font-semibold">Additional payment needed</div>
                        <div class="text-2xl font-extrabold text-amber-300"><span id="balanceDue">₱0.00</span></div>
                    </div>
                </div>

                {{-- ============ ADDITIONAL PAYMENT ============ --}}
                <div id="additionalPaymentPanel" class="hidden mb-5 p-4 rounded-xl border-2 border-amber-300 bg-amber-50">
                    <label class="block mb-2 font-bold text-amber-900">Collect the additional payment</label>
                    <p class="mb-3 text-xs text-amber-800">
                        The new total is higher. Take the extra amount from the customer before saving.
                    </p>

                    <label class="block mb-1 text-xs font-semibold text-slate-600">Method</label>
                    <select name="additional_payment_method" id="additional_payment_method"
                            class="w-full mb-3 px-3 py-2.5 border border-slate-200 rounded-lg bg-white text-sm focus:outline-none focus:border-[#E31E24]">
                        <option value="">Select method</option>
                        <option value="Cash" {{ old('additional_payment_method') === 'Cash' ? 'selected' : '' }}>Cash</option>
                        <option value="GCash" {{ old('additional_payment_method') === 'GCash' ? 'selected' : '' }}>GCash</option>
                    </select>

                    <label class="block mb-1 text-xs font-semibold text-slate-600">Amount received</label>
                    <span class="relative block">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-semibold text-slate-500">₱</span>
                        <input type="number" name="additional_amount_paid" id="additional_amount_paid"
                               value="{{ old('additional_amount_paid') }}"
                               min="0" step="0.01" placeholder="0.00"
                               class="w-full py-2.5 pl-8 pr-3 rounded-lg border border-slate-200 text-sm font-semibold tabular-nums focus:outline-none focus:border-[#E31E24]">
                    </span>
                    <p id="additionalPaymentHint" class="mt-2 text-xs font-semibold text-amber-800"></p>
                </div>

                <button type="submit"
                        class="w-full py-3.5 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-bold shadow-lg shadow-red-500/20 transition">
                    Update Order
                </button>

                <a href="{{ route('orders.show', $order) }}"
                   class="block text-center mt-3 py-3 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition">
                    Cancel
                </a>

                <div class="mt-5 p-3 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-800">
                    <strong>Note:</strong> Stock is adjusted automatically. Adding items requires the
                    extra payment; removing items returns stock.
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
const products = @json($products->mapWithKeys(fn($p) => [$p->product_id => ['price' => (float)$p->price]]));
const previousTotal = {{ (float) $order->total_amount }};

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
    updateBalanceDue(total);
}

function updateBalanceDue(total) {
    const balance = Math.max(0, total - previousTotal);
    const row = document.getElementById('balanceDueRow');
    const panel = document.getElementById('additionalPaymentPanel');
    const hint = document.getElementById('additionalPaymentHint');
    const amountInput = document.getElementById('additional_amount_paid');

    document.getElementById('balanceDue').textContent = '₱' + balance.toFixed(2);

    const needsPayment = balance > 0;
    row.classList.toggle('hidden', !needsPayment);
    panel.classList.toggle('hidden', !needsPayment);

    if (needsPayment) {
        amountInput.required = true;
        const received = parseFloat(amountInput.value) || 0;
        const shortfall = balance - received;

        if (received === 0) {
            hint.textContent = `Collect at least ₱${balance.toFixed(2)} from the customer.`;
            hint.className = 'mt-2 text-xs font-semibold text-amber-800';
        } else if (shortfall > 0) {
            hint.textContent = `Still short by ₱${shortfall.toFixed(2)}.`;
            hint.className = 'mt-2 text-xs font-semibold text-red-700';
        } else {
            hint.textContent = `Covered. Change to give: ₱${(-shortfall).toFixed(2)}.`;
            hint.className = 'mt-2 text-xs font-semibold text-emerald-700';
        }
    } else {
        amountInput.required = false;
        hint.textContent = '';
    }
}

document.getElementById('additional_amount_paid').addEventListener('input', () => {
    let total = 0;
    Object.keys(products).forEach(id => {
        const input = document.getElementById('quantity-' + id);
        if (!input) return;
        total += (parseInt(input.value) || 0) * products[id].price;
    });
    updateBalanceDue(total);
});

document.getElementById('orderForm').addEventListener('submit', function (e) {
    let has = false;
    Object.keys(products).forEach(id => {
        const input = document.getElementById('quantity-' + id);
        if (input && (parseInt(input.value) || 0) > 0) has = true;
    });
    if (!has) { e.preventDefault(); alert('Please select at least one product.'); return; }

    let total = 0;
    Object.keys(products).forEach(id => {
        const input = document.getElementById('quantity-' + id);
        if (!input) return;
        total += (parseInt(input.value) || 0) * products[id].price;
    });

    const balance = Math.max(0, total - previousTotal);
    if (balance > 0) {
        const received = parseFloat(document.getElementById('additional_amount_paid').value) || 0;
        const method = document.getElementById('additional_payment_method').value;

        if (received < balance) {
            e.preventDefault();
            alert(`The order total increased by ₱${balance.toFixed(2)}. Collect at least that amount first.`);
            return;
        }
        if (!method) {
            e.preventDefault();
            alert('Choose a payment method for the additional amount.');
        }
    }
});

updateTotal();
</script>
@endpush

@endsection
