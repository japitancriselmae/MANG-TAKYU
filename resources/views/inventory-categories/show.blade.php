@extends('layouts.app')

@section('title', $inventoryCategory->name)
@section('page-title', $inventoryCategory->name)
@section('page-subtitle', $inventoryCategory->description ?: 'Inventory items in this category.')

@section('content')
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500">{{ $ingredients->total() }} stock item(s)</p>
            <div class="flex gap-2">
                <a href="{{ route('inventory-categories.edit', $inventoryCategory) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Edit category</a>
                <a href="{{ route('inventories.index', ['tab' => 'stock', 'category' => $inventoryCategory->getKey()]) }}" class="rounded-xl bg-[#E31E24] px-4 py-2.5 text-sm font-semibold text-white">Open inventory</a>
            </div>
        </div>
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-4">Stock item</th><th class="px-5 py-4">SKU</th><th class="px-5 py-4">On hand</th><th class="px-5 py-4">Minimum</th><th class="px-5 py-4">Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ingredients as $ingredient)
                        @php($stock = $ingredient->inventories->first())
                        <tr>
                            <td class="px-5 py-4 font-semibold text-slate-900">{{ $ingredient->ingredient_name }}</td>
                            <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $ingredient->sku ?: '—' }}</td>
                            <td class="px-5 py-4">{{ $stock ? number_format((float) $stock->quantity, 2).' '.$ingredient->unit : 'Not tracked' }}</td>
                            <td class="px-5 py-4">{{ number_format((float) ($stock?->minimum_stock ?? $ingredient->minimum_stock), 2).' '.$ingredient->unit }}</td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ !$stock || $stock->isOutOfStock() ? 'bg-red-50 text-red-700' : ($stock->isLowStock() ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700') }}">{{ !$stock || $stock->isOutOfStock() ? 'Out of stock' : ($stock->isLowStock() ? 'Low stock' : 'In stock') }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-16 text-center text-slate-500">No stock items in this category yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($ingredients->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $ingredients->links() }}</div>@endif
        </div>
    </div>
@endsection
