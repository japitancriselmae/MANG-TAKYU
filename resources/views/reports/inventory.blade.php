@extends('layouts.app')

@section('title', 'Inventory report')
@section('page-title', 'Inventory reports')
@section('page-subtitle', 'Run, filter, and print current stock and movement reports.')

@push('head')
    <style>
        @media print {
            #sidebar, #sidebarOverlay, header, .no-print, nav { display: none !important; }
            .lg\:ml-64 { margin-left: 0 !important; }
            body { background: white !important; }
            main, section { box-shadow: none !important; }
        }
    </style>
@endpush

@section('content')
    @php
        $reportTitles = [
            'inventory' => 'Current inventory',
            'stock-in' => 'Stock in',
            'stock-out' => 'Stock out',
            'low-stock' => 'Low stock',
            'out-of-stock' => 'Out of stock',
            'valuation' => 'Inventory valuation',
        ];
    @endphp

    <div class="flex flex-col gap-5">
        <form method="GET" action="{{ route('reports.inventory') }}" class="no-print grid grid-cols-1 gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2 xl:grid-cols-5">
            <label class="text-sm font-semibold text-slate-700">
                Report
                <select name="type" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                    @foreach ($reportTitles as $key => $title)
                        <option value="{{ $key }}" @selected($type === $key)>{{ $title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">
                From
                <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" max="{{ now()->toDateString() }}" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </label>
            <label class="text-sm font-semibold text-slate-700">
                To
                <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" max="{{ now()->toDateString() }}" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </label>
            <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-2">
                <button type="submit" class="rounded-xl bg-[#E31E24] px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Run report</button>
                <a href="{{ route('reports.inventory', ['type' => $type]) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Clear dates</a>
                <button type="button" onclick="window.print()" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Print</button>
            </div>
            @if ($errors->any())
                <p role="alert" class="text-sm text-red-700 sm:col-span-2 xl:col-span-5">{{ $errors->first() }}</p>
            @endif
        </form>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-5 py-5 sm:px-7">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#E31E24]">Mang Takyu · Inventory</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">{{ $reportTitles[$type] }}</h2>
                    <p class="mt-1 text-xs text-slate-500">Generated {{ now()->format('M j, Y · g:i A') }}</p>
                </div>
                @if ($type === 'valuation')
                    <div class="min-w-52 rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-medium text-slate-500">Current stock value</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">₱{{ number_format($valuation, 2) }}</p>
                    </div>
                @else
                    <p class="text-sm font-medium text-slate-500">{{ $isMovementReport ? $movements->total() : $inventories->total() }} record(s)</p>
                @endif
            </div>

            <div class="overflow-x-auto">
                @if ($isMovementReport)
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr><th class="px-5 py-4">Date</th><th class="px-5 py-4">Item</th><th class="px-5 py-4">Category</th><th class="px-5 py-4">Quantity</th><th class="px-5 py-4">Reference</th><th class="px-5 py-4">Recorded by</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($movements as $movement)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $movement->movement_date?->format('M j, Y · g:i A') }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ $movement->ingredient->ingredient_name ?? 'Stock item' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $movement->ingredient->inventoryCategory->name ?? 'Uncategorized' }}</td>
                                    <td class="px-5 py-4 font-semibold tabular-nums {{ $movement->quantity < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format(abs((float) $movement->quantity), 2) }} {{ $movement->ingredient->unit ?? '' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $movement->reference ?? $movement->remarks ?? '—' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $movement->user->name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-16 text-center text-slate-500">No stock movements found for this report period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr><th class="px-5 py-4">Stock item</th><th class="px-5 py-4">SKU</th><th class="px-5 py-4">Category</th><th class="px-5 py-4">On hand</th><th class="px-5 py-4">Minimum</th><th class="px-5 py-4 text-right">Unit cost</th><th class="px-5 py-4 text-right">Stock value</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($inventories as $inventory)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ $inventory->ingredient->ingredient_name ?? 'Stock item' }}</td>
                                    <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $inventory->ingredient->sku ?: '—' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $inventory->ingredient->inventoryCategory->name ?? 'Uncategorized' }}</td>
                                    <td class="px-5 py-4 tabular-nums">{{ number_format((float) $inventory->quantity, 2) }} {{ $inventory->ingredient->unit ?? '' }}</td>
                                    <td class="px-5 py-4 tabular-nums text-slate-600">{{ number_format((float) $inventory->minimum_stock, 2) }} {{ $inventory->ingredient->unit ?? '' }}</td>
                                    <td class="px-5 py-4 text-right tabular-nums">₱{{ number_format((float) $inventory->ingredient->purchase_price, 2) }}</td>
                                    <td class="px-5 py-4 text-right font-semibold tabular-nums">₱{{ number_format((float) $inventory->quantity * (float) $inventory->ingredient->purchase_price, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-16 text-center text-slate-500">No inventory records match this report.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
            <div class="no-print border-t border-slate-100 px-5 py-4">
                @if ($isMovementReport && $movements->hasPages())
                    {{ $movements->links() }}
                @elseif (!$isMovementReport && $inventories->hasPages())
                    {{ $inventories->links() }}
                @endif
            </div>
        </section>
    </div>
@endsection
