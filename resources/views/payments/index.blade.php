@extends('layouts.app')

@section('title', 'Payments')
@section('page-title', 'Payments')
@section('page-subtitle', 'View recorded customer payments.')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Payment Records</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $payments->count() }} payment(s)</p>
    </div>
    <a href="{{ route('orders.index') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
              text-white font-semibold shadow-lg shadow-red-500/20 transition">
        Go to Orders
    </a>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    @if ($payments->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">Payment #</th>
                        <th class="px-6 py-3 font-semibold">Order #</th>
                        <th class="px-6 py-3 font-semibold">Employee</th>
                        <th class="px-6 py-3 font-semibold">Method</th>
                        <th class="px-6 py-3 font-semibold">Paid</th>
                        <th class="px-6 py-3 font-semibold">Change</th>
                        <th class="px-6 py-3 font-semibold">Date</th>
                        <th class="px-6 py-3 font-semibold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($payments as $payment)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                #{{ str_pad($payment->payment_id, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('orders.show', $payment->order) }}"
                                   class="font-bold text-[#E31E24] hover:underline">
                                    #{{ $payment->order->order_id }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                @if ($payment->order?->employee)
                                    {{ $payment->order->employee->first_name }} {{ $payment->order->employee->last_name }}
                                @else
                                    <span class="text-slate-400">Unknown</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($payment->payment_method === 'Cash')
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Cash</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">{{ $payment->payment_method }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-800">
                                ₱{{ number_format($payment->amount_paid, 2) }}
                            </td>
                            <td class="px-6 py-4 font-bold text-emerald-600">
                                ₱{{ number_format($payment->change_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div class="text-slate-700 font-medium">{{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') }}</div>
                                <div class="text-slate-400">{{ \Carbon\Carbon::parse($payment->payment_date)->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end">
                                    <a href="{{ route('payments.show', $payment) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-20 text-center">
            <div class="text-5xl mb-3">💳</div>
            <p class="font-bold text-slate-700">No payments yet</p>
            <p class="text-sm text-slate-500 mt-1">Payments will appear here after orders are paid.</p>
        </div>
    @endif
</div>

@endsection