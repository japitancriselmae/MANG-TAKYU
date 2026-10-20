@extends('layouts.app')

@section('title', 'Transactions')
@section('page-title', 'Transactions')
@section('page-subtitle', 'Review payments and their attached GCash proof.')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Transactions</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $payments->count() }} shown · {{ $allPaymentsCount }} total</p>
    </div>
    <a href="{{ route('orders.index') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
              text-white font-semibold shadow-lg shadow-red-500/20 transition">
        Go to Orders
    </a>
</div>

{{-- ============ FILTERS ============ --}}
<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <nav aria-label="Filter transactions" class="flex flex-wrap gap-2">
        @foreach (['All' => null, 'Cash' => 'Cash', 'GCash' => 'GCash'] as $label => $method)
            <a href="{{ route('payments.index', array_filter(['method' => $method, 'date' => $filterDate?->toDateString()])) }}"
               @class([
                   'rounded-lg px-4 py-2 text-sm font-semibold transition',
                   'bg-[#E31E24] text-white' => $methodFilter === $label,
                   'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $methodFilter !== $label,
               ])>
                {{ $label }}
            </a>
        @endforeach
    </nav>

    <form action="{{ route('payments.index') }}" method="GET"
          class="flex flex-wrap items-end gap-2">
        @if ($methodFilter !== 'All')
            <input type="hidden" name="method" value="{{ $methodFilter }}">
        @endif
        <div>
            <label for="date"
                   class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 mb-1">
                Transaction Date
            </label>
            <input type="date"
                   id="date"
                   name="date"
                   value="{{ $filterDate?->toDateString() }}"
                   max="{{ now()->toDateString() }}"
                   class="px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800
                          focus:outline-none focus:ring-2 focus:ring-[#E31E24] focus:border-transparent">
        </div>
        <button type="submit"
                class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm
                       font-semibold transition">
            Filter
        </button>
        @if ($filterDate)
            <a href="{{ route('payments.index', $methodFilter !== 'All' ? ['method' => $methodFilter] : []) }}"
               class="px-4 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-slate-600 text-sm
                      font-semibold hover:bg-slate-50 transition">
                Clear
            </a>
        @endif
        @error('date')
            <p class="w-full text-xs font-semibold text-[#E31E24]">{{ $message }}</p>
        @enderror
    </form>
</div>

@if ($filterDate)
    <p class="mb-4 text-sm text-slate-500">
        Showing transactions for
        <span class="font-semibold text-slate-700">{{ $filterDate->format('F j, Y') }}</span>
        @if ($payments->isEmpty())
            <span class="text-slate-400">— none recorded</span>
        @endif
    </p>
@endif

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
                        <th class="px-6 py-3 font-semibold">GCash Proof</th>
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
                            <td class="px-6 py-4">
                                @if ($payment->gcash_proof_path)
                                    <a href="{{ route('payments.proof', $payment) }}" target="_blank" rel="noopener"
                                       class="text-xs font-semibold text-blue-700 hover:underline">View image</a>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
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
            <p class="text-sm text-slate-500 mt-1">
                @if ($filterDate)
                    There were no transactions on {{ $filterDate->format('F j, Y') }}.
                @else
                    Payments will appear here after orders are paid.
                @endif
            </p>
            @if ($filterDate)
                <a href="{{ route('payments.index', $methodFilter !== 'All' ? ['method' => $methodFilter] : []) }}"
                   class="inline-block mt-6 px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700
                          text-white font-semibold transition">
                    View All Transactions
                </a>
            @endif
        </div>
    @endif
</div>

@endsection