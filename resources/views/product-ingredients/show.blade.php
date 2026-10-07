@extends('layouts.app')

@section('title', 'Recipe Ingredient')
@section('page-title', 'Recipe Ingredient')
@section('page-subtitle', 'Product recipe ingredient details.')

@section('content')

<div class="max-w-3xl">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <div class="space-y-5">
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Recipe ID</div>
                <div class="text-lg font-bold text-slate-800">
                    #{{ str_pad($productIngredient->product_ingredient_id, 3, '0', STR_PAD_LEFT) }}
                </div>
            </div>

            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Product</div>
                <div class="text-lg font-bold text-slate-800">
                    {{ $productIngredient->product->product_name ?? 'Unknown Product' }}
                </div>
            </div>

            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Ingredient</div>
                <div class="text-lg font-bold text-slate-800">
                    {{ $productIngredient->ingredient->ingredient_name ?? 'Unknown Ingredient' }}
                </div>
            </div>

            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Quantity Required</div>
                <div class="text-lg font-bold text-[#E31E24]">
                    {{ number_format($productIngredient->quantity_required, 2) }}
                    {{ $productIngredient->ingredient->unit ?? '' }}
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-8">
            <a href="{{ route('product-ingredients.edit', $productIngredient) }}"
               class="px-5 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
                Edit Recipe
            </a>
            <a href="{{ route('products.show', $productIngredient->product_id) }}"
               class="px-5 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">
                Back to Product
            </a>
        </div>
    </div>
</div>

@endsection