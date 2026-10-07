@extends('layouts.app')

@section('title', 'Kitchen Queue')
@section('page-title', 'Kitchen Queue')
@section('page-subtitle', 'Manage confirmed and preparing orders.')

@section('content')

@php
    $activeCount = $orders->count();
@endphp

{{-- KPI strip --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-[#E31E24] text-white flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"/>
            </svg>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Active Orders</div>
            <div class="text-3xl font-bold text-slate-800">{{ $activeCount }}</div>
        </div>
    </div>
</div>

@if ($activeCount > 0)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        @foreach ($orders as $order)
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

                {{-- HEADER --}}
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">Order #{{ $order->order_id }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $order->order_type }} · {{ $order->order_date->format('h:i A') }}
                        </p>
                    </div>
                    @if ($order->status === 'Confirmed')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            Confirmed
                        </span>
                    @elseif ($order->status === 'Preparing')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                            Preparing
                        </span>
                    @endif
                </div>

                {{-- FRESH CHICKEN TIMER --}}
                @if ($order->is_fresh_chicken && $order->estimated_ready_at)
                    <div class="px-6 py-4 bg-amber-50 border-b border-amber-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-amber-700 uppercase tracking-wider">🍗 Fresh Chicken</p>
                            <p class="text-[11px] text-amber-600 mt-0.5">Estimated ready time</p>
                        </div>
                        <div class="text-3xl font-bold text-amber-700 tabular-nums countdown"
                             data-ready-time="{{ $order->estimated_ready_at->toIso8601String() }}">
                            --:--
                        </div>
                    </div>
                @endif

                {{-- ITEMS --}}
                <div class="p-6">
                    <h4 class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-3">Order Items</h4>
                    <div class="space-y-2">
                        @foreach ($order->orderItems as $item)
                            <div class="flex justify-between items-center py-2 border-b border-slate-100 last:border-0">
                                <div>
                                    <div class="font-semibold text-slate-800 text-sm">
                                        {{ $item->product?->product_name ?? 'Product Deleted' }}
                                    </div>
                                    <div class="text-xs text-slate-500">₱{{ number_format($item->unit_price, 2) }} each</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-slate-800">× {{ $item->quantity }}</div>
                                    <div class="text-xs text-slate-500">₱{{ number_format($item->subtotal, 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- TOTAL --}}
                    <div class="flex justify-between items-center mt-5 pt-5 border-t border-slate-200">
                        <span class="font-semibold text-slate-700">Total</span>
                        <span class="text-2xl font-bold text-[#E31E24]">₱{{ number_format($order->total_amount, 2) }}</span>
                    </div>

                    {{-- EMPLOYEE --}}
                    <div class="mt-4 p-3 bg-slate-50 rounded-xl">
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Taken by</p>
                        <p class="font-semibold text-slate-800 text-sm mt-0.5">
                            {{ $order->employee?->first_name }} {{ $order->employee?->last_name }}
                        </p>
                    </div>

                    {{-- ACTION --}}
                    <div class="mt-5">
                        @if ($order->status === 'Confirmed')
                            <form action="{{ route('orders.preparing', $order) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold
                                               shadow-lg shadow-purple-500/20 transition">
                                    Start Preparing
                                </button>
                            </form>
                        @elseif ($order->status === 'Preparing')
                            <form action="{{ route('orders.ready', $order) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold
                                               shadow-lg shadow-emerald-500/20 transition">
                                    Mark as Ready
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-6 py-20 text-center">
        <div class="text-5xl mb-3">✓</div>
        <h3 class="text-xl font-bold text-slate-700">No Active Orders</h3>
        <p class="text-sm text-slate-500 mt-1">
            There are currently no confirmed or preparing orders.
        </p>
    </div>
@endif

@push('scripts')
<script>
function updateCountdowns() {
    document.querySelectorAll('.countdown').forEach(el => {
        const readyTime = new Date(el.dataset.readyTime).getTime();
        const diff = readyTime - Date.now();

        if (diff <= 0) {
            el.textContent = '00:00';
            el.classList.remove('text-amber-700');
            el.classList.add('text-red-600');
            return;
        }
        const totalSec = Math.floor(diff / 1000);
        const m = String(Math.floor(totalSec / 60)).padStart(2, '0');
        const s = String(totalSec % 60).padStart(2, '0');
        el.textContent = m + ':' + s;
    });
}
updateCountdowns();
setInterval(updateCountdowns, 1000);
</script>
@endpush

@endsection