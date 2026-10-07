@extends('layouts.app')

@section('title', 'Ingredient Details')
@section('page-title', 'Ingredient Details')
@section('page-subtitle', 'View information about this ingredient.')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Basic Information</h2>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('ingredients.edit', $ingredient->ingredient_id) }}"
           class="px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
            Edit Ingredient
        </a>
        <a href="{{ route('ingredients.index') }}"
           class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">
            ← Back
        </a>
    </div>
</div>

{{-- Info card --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8 mb-5">
    <div class="flex items-start gap-5 mb-6">
        <div class="w-16 h-16 rounded-2xl bg-[#0B0B0B] text-[#F5A623] flex items-center justify-center font-extrabold text-2xl">
            {{ strtoupper(substr($ingredient->ingredient_name, 0, 1)) }}
        </div>
        <div>
            <h2 class="text-2xl font-bold text-slate-800">{{ $ingredient->ingredient_name }}</h2>
            <div class="mt-1">
                @if ($ingredient->status === 'Active')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Active
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                        Inactive
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-5 border-t border-slate-100">
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Ingredient ID</div>
            <div class="text-lg font-bold text-slate-800">
                #{{ str_pad($ingredient->ingredient_id, 3, '0', STR_PAD_LEFT) }}
            </div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Unit</div>
            <div class="text-lg font-bold text-slate-800">{{ $ingredient->unit }}</div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Minimum Stock</div>
            <div class="text-lg font-bold text-slate-800">
                {{ number_format($ingredient->minimum_stock, 2) }} {{ $ingredient->unit }}
            </div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Created</div>
            <div class="text-lg font-semibold text-slate-800">
                {{ $ingredient->created_at?->format('M d, Y') }}
            </div>
        </div>
    </div>
</div>

{{-- Related counts --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Inventory Records</div>
        <div class="text-3xl font-bold text-[#E31E24]">{{ $ingredient->inventories->count() }}</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Stock Movements</div>
        <div class="text-3xl font-bold text-amber-600">{{ $ingredient->stockMovements->count() }}</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Used in Recipes</div>
        <div class="text-3xl font-bold text-emerald-600">{{ $ingredient->productIngredients->count() }}</div>
    </div>
</div>

@endsection