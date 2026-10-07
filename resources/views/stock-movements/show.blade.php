@extends('layouts.app')

@section('title', 'Stock Movement Details')
@section('page-title', 'Stock Movement Details')
@section('page-subtitle', 'View the stock movement record.')

@section('content')

<div class="max-w-3xl">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <div class="space-y-5">
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Movement ID</div>
                <div class="text-lg font-bold text-slate-800">
                    #{{ str_pad($stockMovement->movement_id, 3, '0', STR_PAD_LEFT) }}
                </div>
            </div>

            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Ingredient</div>
                <div class="text-lg font-bold text-slate-800">
                    {{ $stockMovement->ingredient->ingredient_name ?? 'Unknown Ingredient' }}
                </div>
            </div>

            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Movement Type</div>
                <div class="mt-1">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                        {{ $stockMovement->movement_type }}
                    </span>
                </div>
            </div>

            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Quantity</div>
                <div class="text-2xl font-bold text-slate-800">
                    {{ number_format($stockMovement->quantity, 2) }}
                    {{ $stockMovement->ingredient->unit ?? '' }}
                </div>
            </div>

            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Movement Date</div>
                <div class="text-lg font-semibold text-slate-800">
                    {{ \Carbon\Carbon::parse($stockMovement->movement_date)->format('F d, Y h:i A') }}
                </div>
            </div>

            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Reference</div>
                <div class="text-lg text-slate-800">
                    {{ $stockMovement->reference ?: '—' }}
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-8">
            <a href="{{ route('stock-movements.edit', $stockMovement) }}"
               class="px-5 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
                Edit
            </a>
            <a href="{{ route('stock-movements.index') }}"
               class="px-5 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">
                Back to List
            </a>
        </div>
    </div>
</div>

@endsection