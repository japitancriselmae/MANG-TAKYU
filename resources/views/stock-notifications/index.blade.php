@extends('layouts.app')

@section('title', 'Stock Notifications')
@section('page-title', 'Stock Notifications')
@section('page-subtitle', 'Monitor ingredients that are out of stock, low in stock, or at normal levels.')

@section('content')

{{-- SUMMARY --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Out of Stock</div>
        <div class="text-3xl font-bold text-[#E31E24]">{{ $outOfStockInventories->count() }}</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Low Stock</div>
        <div class="text-3xl font-bold text-amber-600">{{ $lowStockInventories->count() }}</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Normal</div>
        <div class="text-3xl font-bold text-emerald-600">{{ $normalStockInventories->count() }}</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Total</div>
        <div class="text-3xl font-bold text-slate-800">{{ $inventories->count() }}</div>
    </div>
</div>

{{-- OUT OF STOCK --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-bold text-slate-800">🔴 Out of Stock</h3>
        <span class="px-3 py-1 rounded-full bg-red-100 text-[#E31E24] text-xs font-bold">
            {{ $outOfStockInventories->count() }}
        </span>
    </div>
    @if ($outOfStockInventories->isEmpty())
        <div class="px-6 py-10 text-center">
            <div class="text-3xl mb-2">✓</div>
            <p class="text-sm font-semibold text-emerald-700">No ingredients are out of stock</p>
        </div>
    @else
        <div class="divide-y divide-slate-100">
            @foreach ($outOfStockInventories as $inventory)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-100 text-[#E31E24] flex items-center justify-center font-bold">!</div>
                        <div>
                            <div class="font-semibold text-slate-800">
                                {{ $inventory->ingredient?->ingredient_name ?? 'Unknown Ingredient' }}
                            </div>
                            <div class="text-xs text-slate-500">
                                Minimum stock: {{ number_format((float) $inventory->minimum_stock, 2) }}
                                {{ $inventory->ingredient?->unit ?? '' }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-[#E31E24]">
                            {{ number_format((float) $inventory->quantity, 2) }}
                        </div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Current Qty</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- LOW STOCK --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-bold text-slate-800">🟠 Low Stock</h3>
        <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">
            {{ $lowStockInventories->count() }}
        </span>
    </div>
    @if ($lowStockInventories->isEmpty())
        <div class="px-6 py-10 text-center">
            <div class="text-3xl mb-2">✓</div>
            <p class="text-sm font-semibold text-emerald-700">All available ingredients are above their minimum stock level</p>
        </div>
    @else
        <div class="divide-y divide-slate-100">
            @foreach ($lowStockInventories as $inventory)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center font-bold">!</div>
                        <div>
                            <div class="font-semibold text-slate-800">
                                {{ $inventory->ingredient?->ingredient_name ?? 'Unknown Ingredient' }}
                            </div>
                            <div class="text-xs text-slate-500">
                                Minimum: {{ number_format((float) $inventory->minimum_stock, 2) }}
                                {{ $inventory->ingredient?->unit ?? '' }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-amber-600">
                            {{ number_format((float) $inventory->quantity, 2) }}
                        </div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Current Qty</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- NORMAL STOCK --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-bold text-slate-800">🟢 Normal Stock</h3>
        <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">
            {{ $normalStockInventories->count() }}
        </span>
    </div>
    @if ($normalStockInventories->isEmpty())
        <div class="px-6 py-10 text-center">
            <p class="text-sm text-slate-500">No ingredients currently have normal stock.</p>
        </div>
    @else
        <div class="divide-y divide-slate-100">
            @foreach ($normalStockInventories as $inventory)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold">✓</div>
                        <div>
                            <div class="font-semibold text-slate-800">
                                {{ $inventory->ingredient?->ingredient_name ?? 'Unknown Ingredient' }}
                            </div>
                            <div class="text-xs text-slate-500">
                                Minimum: {{ number_format((float) $inventory->minimum_stock, 2) }}
                                {{ $inventory->ingredient?->unit ?? '' }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-emerald-600">
                            {{ number_format((float) $inventory->quantity, 2) }}
                        </div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Current Qty</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection