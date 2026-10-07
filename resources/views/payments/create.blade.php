@extends('layouts.app')

@section('title', 'Record Payment — Order #' . $order->order_id)
@section('page-title', 'Record Payment')
@section('page-subtitle', 'Order #' . $order->order_id)

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

    {{-- ORDER SUMMARY --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="font-bold text-slate-800 mb-5">Order Summary</h3>

        <div class="space-y-3 mb-6">
            <div class="flex justify-between">
                <span class="text-slate-500 text-sm">Order Number</span>
                <span class="font-bold text-slate-800">#{{ $order->order_id }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500 text-sm">Order Type</span>
                <span class="font-semibold text-slate-800">{{ $order->order_type }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500 text-sm">Status</span>
                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                    {{ $order->status }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500 text-sm">Employee</span>
                <span class="font-semibold text-slate-800">
                    {{ $order->employee?->first_name }} {{ $order->employee?->last_name }}
                </span>
            </div>
        </div>

        <div class="pt-5 border-t border-slate-100">
            <h4 class="font-semibold text-slate-800 text-sm mb-3">Items</h4>
            <div class="space-y-2">
                @foreach ($order->orderItems as $item)
                    <div class="flex justify-between text-sm">
                        <div>
                            <span class="font-medium text-slate-800">{{ $item->product?->product_name ?? 'Unknown' }}</span>
                            <span class="text-slate-500 text-xs ml-1">{{ $item->quantity }} × ₱{{ number_format($item->unit_price, 2) }}</span>
                        </div>
                        <span class="font-semibold text-slate-800">₱{{ number_format($item->subtotal, 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-6 pt-5 border-t border-slate-100 flex justify-between items-center">
            <span class="text-lg font-bold text-slate-700">Total</span>
            <span class="text-3xl font-bold text-[#E31E24]">₱{{ number_format($order->total_amount, 2) }}</span>
        </div>
    </div>

    {{-- PAYMENT FORM --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="font-bold text-slate-800 mb-5">Payment Details</h3>

        <form action="{{ route('payments.store') }}" method="POST" id="paymentForm">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->order_id }}">

            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Payment Method</label>
                <select name="payment_method" id="payment_method" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    <option value="">Select payment method</option>
                    <option value="Cash"  {{ old('payment_method') === 'Cash'  ? 'selected' : '' }}>Cash</option>
                    <option value="GCash" {{ old('payment_method') === 'GCash' ? 'selected' : '' }}>GCash</option>
                </select>
            </div>

            <div class="mb-5">
                <label for="amount_paid" class="block text-sm font-semibold text-slate-700 mb-2">Amount Paid</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-semibold">₱</span>
                    <input type="number" name="amount_paid" id="amount_paid" step="0.01" min="0"
                           value="{{ old('amount_paid') }}" required placeholder="0.00"
                           class="w-full pl-9 pr-4 py-3 border border-slate-200 rounded-xl text-lg font-semibold
                                  focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                </div>
            </div>

            <div class="mb-6 p-5 bg-slate-50 rounded-xl">
                <div class="flex justify-between items-center">
                    <span class="font-semibold text-slate-700">Change</span>
                    <span id="changeAmount" class="text-2xl font-bold text-emerald-600">₱0.00</span>
                </div>
            </div>

            <button type="submit"
                    class="w-full py-3.5 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-bold shadow-lg shadow-red-500/20 transition">
                Record Payment
            </button>

            <a href="{{ route('orders.show', $order) }}"
               class="block text-center mt-3 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">
                Cancel
            </a>

            <div class="mt-5 p-3 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-800">
                Payment can only be recorded after the order is marked as <strong>Ready</strong>.
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const orderTotal = {{ (float) $order->total_amount }};
const amountInput = document.getElementById('amount_paid');
const changeDisplay = document.getElementById('changeAmount');

function calculateChange() {
    const amountPaid = parseFloat(amountInput.value) || 0;
    const change = amountPaid - orderTotal;
    changeDisplay.textContent = '₱' + (change >= 0 ? change.toFixed(2) : '0.00');
}
amountInput.addEventListener('input', calculateChange);
calculateChange();
</script>
@endpush

@endsection