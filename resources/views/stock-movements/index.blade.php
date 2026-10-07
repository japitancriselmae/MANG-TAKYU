@extends('layouts.app')

@section('title', 'Stock Movements')
@section('page-title', 'Stock Movements')
@section('page-subtitle', 'Track stock additions and inventory adjustments.')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Movement List</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $stockMovements->count() }} record(s)</p>
    </div>
    <a href="{{ route('stock-movements.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
              text-white font-semibold shadow-lg shadow-red-500/20 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Record Movement
    </a>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    @if ($stockMovements->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">ID</th>
                        <th class="px-6 py-3 font-semibold">Ingredient</th>
                        <th class="px-6 py-3 font-semibold">Type</th>
                        <th class="px-6 py-3 font-semibold">Quantity</th>
                        <th class="px-6 py-3 font-semibold">Date</th>
                        <th class="px-6 py-3 font-semibold">Reference</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($stockMovements as $movement)
                        @php
                            $type = strtolower(trim($movement->movement_type ?? ''));
                            $qty = (float) $movement->quantity;
                            $isIn = in_array($type, ['in', 'stock in', 'addition', 'add']);
                            $isOut = in_array($type, ['out', 'stock out', 'deduction', 'deduct']);
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                #{{ str_pad($movement->movement_id, 3, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                {{ $movement->ingredient->ingredient_name ?? 'Unknown Ingredient' }}
                            </td>
                            <td class="px-6 py-4">
                                @if ($isIn)
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                        Stock In
                                    </span>
                                @elseif ($isOut)
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-[#E31E24]">
                                        Stock Out
                                    </span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                        {{ $movement->movement_type }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($qty > 0)
                                    <span class="font-bold text-emerald-600">+{{ number_format($qty, 2) }}</span>
                                @elseif ($qty < 0)
                                    <span class="font-bold text-[#E31E24]">{{ number_format($qty, 2) }}</span>
                                @else
                                    <span class="text-slate-500">{{ number_format($qty, 2) }}</span>
                                @endif
                                <span class="text-xs text-slate-500">{{ $movement->ingredient->unit ?? '' }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-xs">
                                {{ $movement->movement_date ? $movement->movement_date->format('M d, Y h:i A') : '—' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-xs">
                                {{ $movement->reference ?? '—' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('stock-movements.show', $movement) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        View
                                    </a>
                                    <a href="{{ route('stock-movements.edit', $movement) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('stock-movements.destroy', $movement) }}" method="POST"
                                          onsubmit="return confirm('Delete this record?');">
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
            <div class="text-5xl mb-3">📊</div>
            <p class="font-bold text-slate-700">No stock movements yet</p>
            <p class="text-sm text-slate-500 mt-1 mb-6">Record your first stock movement to get started.</p>
            <a href="{{ route('stock-movements.create') }}"
               class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                Record First Movement
            </a>
        </div>
    @endif
</div>

@endsection