@extends('layouts.app')

@section('title', 'Inventory Details')
@section('page-title', 'Inventory Details')
@section('page-subtitle', 'View the current stock information.')

@section('content')

@php
    $isLow = $inventory->quantity <= $inventory->ingredient->minimum_stock;
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Stock Information</h2>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('inventories.edit', $inventory->inventory_id) }}"
           class="px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
            Edit
        </a>
        <a href="{{ route('inventories.index') }}"
           class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">
            ← Back
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Inventory ID</div>
            <div class="text-lg font-bold text-slate-800">
                #{{ str_pad($inventory->inventory_id, 3, '0', STR_PAD_LEFT) }}
            </div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Ingredient</div>
            <div class="text-lg font-bold text-slate-800">
                {{ $inventory->ingredient->ingredient_name }}
            </div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Current Quantity</div>
            <div class="text-2xl font-bold {{ $isLow ? 'text-[#E31E24]' : 'text-emerald-600' }}">
                {{ number_format($inventory->quantity, 2) }} {{ $inventory->ingredient->unit }}
            </div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Minimum Stock</div>
            <div class="text-lg font-bold text-slate-800">
                {{ number_format($inventory->ingredient->minimum_stock, 2) }} {{ $inventory->ingredient->unit }}
            </div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Stock Status</div>
            <div class="mt-1">
                @if ($isLow)
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-[#E31E24]">Low Stock</span>
                @else
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Sufficient</span>
                @endif
            </div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Last Updated</div>
            <div class="text-lg font-semibold text-slate-800">
                {{ \Carbon\Carbon::parse($inventory->last_updated)->format('M d, Y h:i A') }}
            </div>
        </div>
    </div>
</div>

@endsection