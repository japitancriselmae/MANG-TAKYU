@extends('layouts.app')

@section('title', 'Daily Transaction Report')
@section('page-title', 'Daily Transaction Report')
@section('page-subtitle', 'Generate a printable transaction log for any day.')

@push('head')
    <style>
        .print-only {
            display: none;
        }

        /* ============ PRINT LAYOUT ============ */
        @media print {
            @page {
                margin: 14mm;
            }

            body {
                background: #fff !important;
            }

            /* Hide screen-only chrome so the sheet is the only output */
            #sidebar,
            #sidebarOverlay,
            #sidebarToggle,
            header,
            .no-print {
                display: none !important;
            }

            /* Remove the sidebar gutter so the report uses the full width */
            .lg\:ml-64 {
                margin-left: 0 !important;
            }

            main {
                padding: 0 !important;
            }

            .report-sheet {
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }

            .report-table tr {
                page-break-inside: avoid;
            }

            .report-table thead {
                display: table-header-group;
            }

            .report-table th,
            .report-table td {
                border-bottom: 1px solid #cbd5e1 !important;
            }

            .print-only {
                display: block !important;
            }
        }
    </style>
@endpush

@section('content')

<div class="report-sheet bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

    {{-- ============ REPORT HEADER ============ --}}
    <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b-2 border-slate-800">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/mang-takyu-logo.png') }}"
                 alt="Mang Takyu"
                 class="h-14 w-14 rounded-full object-cover bg-white ring-2 ring-[#E31E24]">
            <div class="leading-tight">
                <div class="text-lg font-extrabold tracking-wide text-slate-900">MANG TAKYU</div>
                <div class="text-[10px] uppercase tracking-widest text-[#F5A623]">
                    Daily Transaction Report
                </div>
            </div>
        </div>
        <div class="text-right text-sm text-slate-600">
            <div class="font-bold text-slate-900">{{ $reportDate->format('F j, Y') }}</div>
            <div class="text-xs text-slate-500 print-only">
                Generated {{ now()->format('M j, Y h:i A') }}
            </div>
            <a href="{{ route('reports.sales', ['date' => $reportDate->toDateString()]) }}"
               class="mt-2 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
                      text-white text-sm font-semibold shadow-lg shadow-red-500/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 6.253v13m0-13C10.832 8.088 9.246 9 7.5 9S4.168 8.088 3 6.253m-0.5h15M12 6.253a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Sales Report
            </a>
        </div>
    </div>

    {{-- ============ SUMMARY TOTALS ============ --}}
    <div class="grid grid-cols-2 gap-4 py-6 sm:grid-cols-4">
        <div>
            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Transactions</div>
            <div class="mt-1 text-xl font-bold text-slate-900">{{ $transactionCount }}</div>
        </div>
        <div>
            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Net Sales</div>
            <div class="mt-1 text-xl font-bold text-[#E31E24]">₱{{ number_format($netSales, 2) }}</div>
        </div>
        <div>
            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Cash Sales</div>
            <div class="mt-1 text-xl font-bold text-slate-900">₱{{ number_format($cashSales, 2) }}</div>
        </div>
        <div>
            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">GCash Sales</div>
            <div class="mt-1 text-xl font-bold text-slate-900">₱{{ number_format($gcashSales, 2) }}</div>
        </div>
    </div>

    {{-- ============ DATE FILTER + PRINT (SCREEN ONLY) ============ --}}
    <div class="no-print mb-6 flex flex-wrap items-end justify-between gap-4">
        <form action="{{ route('reports.transactions') }}" method="GET"
              class="flex flex-wrap items-end gap-3">
            <div>
                <label for="date"
                       class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                    Report Date
                </label>
                <input type="date"
                       id="date"
                       name="date"
                       value="{{ $reportDate->toDateString() }}"
                       max="{{ now()->toDateString() }}"
                       class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800
                              focus:outline-none focus:ring-2 focus:ring-[#E31E24] focus:border-transparent">
            </div>
            <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm
                           font-semibold transition">
                View Report
            </button>
            @error('date')
                <p class="w-full text-xs font-semibold text-[#E31E24]">{{ $message }}</p>
            @enderror
        </form>

        <button type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
                       text-white text-sm font-semibold shadow-lg shadow-red-500/20 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/>
            </svg>
            Print Report
        </button>
    </div>

    {{-- ============ TRANSACTION TABLE ============ --}}
    @if ($payments->isEmpty())
        <div class="px-6 py-20 text-center">
            <div class="text-5xl mb-3">🧾</div>
            <p class="font-bold text-slate-700">No transactions recorded</p>
            <p class="text-sm text-slate-500 mt-1">
                There were no payments on {{ $reportDate->format('F j, Y') }}.
            </p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="report-table w-full text-left text-sm">
                <thead>
                    <tr class="text-[10px] uppercase tracking-wider text-slate-500 border-b-2 border-slate-800">
                        <th class="py-3 pr-4 font-semibold">Time</th>
                        <th class="py-3 pr-4 font-semibold">Payment ID</th>
                        <th class="py-3 pr-4 font-semibold">Order</th>
                        <th class="py-3 pr-4 font-semibold">Cashier</th>
                        <th class="py-3 pr-4 font-semibold">Items</th>
                        <th class="py-3 pr-4 font-semibold">Method</th>
                        <th class="py-3 pr-4 font-semibold text-right">Amount Paid</th>
                        <th class="py-3 pr-4 font-semibold text-right">Change</th>
                        <th class="py-3 font-semibold text-right">Net Sales</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($payments as $payment)
                        @php
                            $netSale = (float) $payment->amount_paid - (float) $payment->change_amount;
                            $order = $payment->order;
                            $itemCount = $order?->orderItems?->sum('quantity') ?? 0;
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 pr-4 text-slate-600 whitespace-nowrap">
                                {{ $payment->payment_date->format('h:i A') }}
                            </td>
                            <td class="py-3 pr-4 font-mono text-xs text-slate-500">
                                #{{ str_pad($payment->payment_id, 3, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="py-3 pr-4 font-mono text-xs text-slate-500">
                                #{{ str_pad($payment->order_id, 3, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="py-3 pr-4 text-slate-700">
                                @if ($order?->employee)
                                    {{ $order->employee->first_name }} {{ $order->employee->last_name }}
                                @else
                                    <span class="text-slate-400">Unknown</span>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-slate-600">{{ $itemCount }}</td>
                            <td class="py-3 pr-4">
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold
                                             {{ $payment->payment_method === 'GCash'
                                                    ? 'bg-blue-100 text-blue-700'
                                                    : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $payment->payment_method }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-right font-bold text-slate-800">
                                ₱{{ number_format($payment->amount_paid, 2) }}
                            </td>
                            <td class="py-3 pr-4 text-right text-slate-600">
                                ₱{{ number_format($payment->change_amount, 2) }}
                            </td>
                            <td class="py-3 text-right font-bold text-slate-900">
                                ₱{{ number_format($netSale, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-800 text-sm">
                        <td colspan="6" class="py-3 pr-4 font-bold text-slate-700">
                            Totals ({{ $transactionCount }} transaction(s))
                        </td>
                        <td class="py-3 pr-4 text-right font-bold text-slate-900">
                            ₱{{ number_format($totalCollected, 2) }}
                        </td>
                        <td class="py-3 pr-4 text-right font-bold text-slate-900">
                            ₱{{ number_format($totalChangeGiven, 2) }}
                        </td>
                        <td class="py-3 text-right font-bold text-[#E31E24]">
                            ₱{{ number_format($netSales, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- ============ SIGN-OFF (PRINT ONLY) ============ --}}
    <div class="print-only mt-16 grid grid-cols-2 gap-16 text-sm">
        <div>
            <div class="border-t border-slate-800 pt-2 text-slate-600">Prepared by</div>
            <div class="mt-1 font-semibold text-slate-900">{{ auth()->user()->name }}</div>
            <div class="text-xs text-slate-500">{{ auth()->user()->employee?->role?->role_name }}</div>
        </div>
        <div>
            <div class="border-t border-slate-800 pt-2 text-slate-600">Received by</div>
            <div class="mt-10"></div>
        </div>
    </div>

    <p class="print-only mt-8 text-center text-[10px] text-slate-400">
        Mang Takyu Management System — Daily Transaction Report
    </p>
</div>

@endsection
