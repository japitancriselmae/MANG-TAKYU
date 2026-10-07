@extends('layouts.app')

@section('title', 'Inventory')
@section('page-title', 'Inventory')
@section('page-subtitle', 'Monitor current ingredient quantities and low-stock levels.')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Inventory Records</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $inventories->count() }} record(s)</p>
    </div>
    <a href="{{ route('inventories.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
              text-white font-semibold shadow-lg shadow-red-500/20 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add Inventory
    </a>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    @if ($inventories->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">ID</th>
                        <th class="px-6 py-3 font-semibold">Ingredient</th>
                        <th class="px-6 py-3 font-semibold">Current Qty</th>
                        <th class="px-6 py-3 font-semibold">Minimum</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold">Last Updated</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($inventories as $inventory)
                        @php
                            $isLow = $inventory->quantity <= $inventory->ingredient->minimum_stock;
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                #{{ str_pad($inventory->inventory_id, 3, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-800">
                                    {{ $inventory->ingredient->ingredient_name }}
                                </div>
                                <div class="text-xs text-slate-500">ID: {{ $inventory->ingredient_id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-800">
                                    {{ number_format($inventory->quantity, 2) }}
                                </span>
                                <span class="text-xs text-slate-500">{{ $inventory->ingredient->unit }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ number_format($inventory->ingredient->minimum_stock, 2) }}
                                {{ $inventory->ingredient->unit }}
                            </td>
                            <td class="px-6 py-4">
                                @if ($isLow)
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-[#E31E24]">
                                        Low Stock
                                    </span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                        Sufficient
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-xs">
                                {{ \Carbon\Carbon::parse($inventory->last_updated)->format('M d, Y h:i A') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('inventories.show', $inventory->inventory_id) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        View
                                    </a>
                                    <a href="{{ route('inventories.edit', $inventory->inventory_id) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('inventories.destroy', $inventory->inventory_id) }}" method="POST"
                                          onsubmit="return confirm('Delete this inventory record?');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-20 text-center">
            <div class="text-5xl mb-3">📦</div>
            <p class="font-bold text-slate-700">No inventory records yet</p>
            <p class="text-sm text-slate-500 mt-1 mb-6">Add inventory to start tracking stock.</p>
            <a href="{{ route('inventories.create') }}"
               class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                Add First Inventory
            </a>
        </div>
    @endif
</div>

@endsection