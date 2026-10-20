@extends('layouts.app')

@section('title', 'Sales Report')
@section('page-title', 'Sales Report')
@section('page-subtitle', 'View daily sales performance and top-selling items.')

@push('head')
    <style>
        .bar-bg {
            background: #e2e8f0;
        }
        .bar-fill {
            background: #F5A623;
        }
    </style>
@endpush

@section('content')

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

    {{-- ============ REPORT HEADER ============ --}}
    <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b-2 border-slate-800">
        <div class="flex items-center gap-3">
            <a href="{{ route('reports.transactions', ['date' => $reportDate->toDateString()]) }}"
               class="flex items-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 px-4 py-2.5 text-white text-sm font-semibold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 19l-7-7 7-7"/>
                </svg>
                Back
            </a>
            <img src="{{ asset('images/mang-takyu-logo.png') }}"
                 alt="Mang Takyu"
                 class="h-14 w-14 rounded-full object-cover bg-white ring-2 ring-[#E31E24]">
            <div class="leading-tight">
                <div class="text-lg font-extrabold tracking-wide text-slate-900">MANG TAKYU</div>
                <div class="text-[10px] uppercase tracking-widest text-[#F5A623]">
                    Sales Report
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <form action="{{ route('reports.sales') }}" method="GET"
                  class="flex items-center gap-2">
                <div>
                    <label for="date"
                           class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                        Date
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
                        class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm
                               font-semibold transition">
                    Filter
                </button>
                @error('date')
                    <p class="w-full text-xs font-semibold text-[#E31E24]">{{ $message }}</p>
                @enderror
            </form>
        </div>
    </div>

    {{-- ============ SUMMARY CARDS ============ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 py-6">
        <div class="bg-white rounded-xl border border-slate-100 p-5">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Total Sales</div>
            <div class="text-2xl font-bold text-[#E31E24]">₱{{ number_format($netSales, 2) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-5">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Transactions</div>
            <div class="text-2xl font-bold text-slate-900">{{ $transactionCount }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-5">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Avg Order</div>
            <div class="text-2xl font-bold text-slate-900">₱{{ number_format($averageOrder, 2) }}</div>
        </div>
    </div>

    {{-- ============ TOP SELLING ITEMS ============ --}}
    <div class="bg-white rounded-xl border border-slate-100 p-5">
        <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-4">Top Selling Items</div>

        @if ($topItems->isEmpty())
            <div class="px-6 py-20 text-center">
                <div class="text-5xl mb-3">🍽️</div>
                <p class="font-bold text-slate-700">No items sold</p>
                <p class="text-sm text-slate-500 mt-1">
                    There were no order items on {{ $reportDate->format('F j, Y') }}.
                </p>
            </div>
        @else
            <div class="space-y-3">
                @php $maxQty = $topItems->first()['quantity']; @endphp
                @foreach ($topItems as $item)
                    @php
                        $barWidth = $maxQty > 0 ? round(($item['quantity'] / $maxQty) * 100) : 0;
                    @endphp
                    <div class="flex items-center gap-3">
                        <div class="w-24 text-sm font-semibold text-slate-800 truncate">
                            {{ $item['product']->product_name ?? 'Unknown Product' }}
                        </div>
                        <div class="flex-1 h-3 rounded-full overflow-hidden bar-bg">
                            <div class="bar-fill h-full" style="width: {{ $barWidth }}%;"></div>
                        </div>
                        <div class="w-12 text-right text-sm font-bold text-slate-900">
                            {{ $item['quantity'] }} <span class="text-xs font-normal text-slate-500 ml-1">({{ $item['percentage'] }}%)</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

@endsection
