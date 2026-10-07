@extends('layouts.app')

@section('title', 'Payment #' . $payment->payment_id)
@section('page-title', 'Payment #' . $payment->payment_id)
@section('page-subtitle', 'Payment details for Order #' . $payment->order->order_id)

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

    {{-- PAYMENT INFO --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="font-bold text-slate-800 mb-5">Payment Information</h3>

        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-slate-500 text-sm">Payment Number</span>
                <span class="font-bold text-slate-800">#{{ str_pad($payment->payment_id, 4, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 text-sm">Order Number</span>
                <a href="{{ route('orders.show', $payment->order) }}" class="font-bold text-[#E31E24] hover:underline">
                    #{{ $payment->order->order_id }}
                </a>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 text-sm">Payment Method</span>
                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                    {{ $payment->payment_method }}
                </span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 text-sm">Amount Paid</span>
                <span class="font-bold text-slate-800">₱{{ number_format($payment->amount_paid, 2) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 text-sm">Change</span>
                <span class="font-bold text-emerald-600">₱{{ number_format($payment->change_amount, 2) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 text-sm">Payment Date</span>
                <span class="font-semibold text-slate-800 text-sm">
                    {{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y h:i A') }}
                </span>
            </div>
        </div>
    </div>

    {{-- ORDER INFO --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="font-bold text-slate-800 mb-5">Order Information</h3>

        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-slate-500 text-sm">Order Type</span>
                <span class="font-semibold text-slate-800">{{ $payment->order->order_type }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 text-sm">Order Status</span>
                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                    {{ $payment->order->status }}
                </span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-slate-500 text-sm">Employee</span>
                <span class="font-semibold text-slate-800">
                    {{ $payment->order->employee?->first_name }} {{ $payment->order->employee?->last_name }}
                </span>
            </div>
            <div class="pt-5 border-t border-slate-100 flex justify-between items-center">
                <span class="text-lg font-bold text-slate-700">Order Total</span>
                <span class="text-2xl font-bold text-[#E31E24]">₱{{ number_format($payment->order->total_amount, 2) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ITEMS --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-5">
    <div class="px-6 py-4 border-b border-slate-100">
        <h3 class="font-bold text-slate-800">Order Items</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr class="text-xs uppercase tracking-wider text-slate-500">
                    <th class="px-6 py-3 font-semibold">Product</th>
                    <th class="px-6 py-3 font-semibold text-center">Quantity</th>
                    <th class="px-6 py-3 font-semibold text-right">Unit Price</th>
                    <th class="px-6 py-3 font-semibold text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($payment->order->orderItems as $item)
                    <tr>
                        <td class="px-6 py-4 font-semibold text-slate-800">
                            {{ $item->product?->product_name ?? 'Unknown Product' }}
                        </td>
                        <td class="px-6 py-4 text-center text-slate-600">{{ $item->quantity }}</td>
                        <td class="px-6 py-4 text-right text-slate-600">₱{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-6 py-4 text-right font-bold text-slate-800">₱{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- RECEIPT --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
    <div class="max-w-md ml-auto space-y-3">
        <div class="flex justify-between">
            <span class="text-slate-500">Order Total</span>
            <span class="font-semibold text-slate-800">₱{{ number_format($payment->order->total_amount, 2) }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500">Amount Paid</span>
            <span class="font-semibold text-slate-800">₱{{ number_format($payment->amount_paid, 2) }}</span>
        </div>
        <div class="pt-3 border-t border-slate-200 flex justify-between">
            <span class="text-lg font-bold text-slate-700">Change</span>
            <span class="text-xl font-bold text-emerald-600">₱{{ number_format($payment->change_amount, 2) }}</span>
        </div>
    </div>

    <div class="mt-6 flex gap-3">
        <a href="{{ route('payments.index') }}"
           class="px-5 py-3 rounded-xl bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200 transition">
            ← Payments
        </a>
        <a href="{{ route('orders.show', $payment->order) }}"
           class="px-5 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
            View Order
        </a>
    </div>
</div>

@endsection