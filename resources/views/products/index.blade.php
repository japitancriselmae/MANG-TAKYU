@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')
@section('page-subtitle', 'Manage products and their recipes.')

@section('content')

@php
    $activeTab = $activeTab ?? 'products';
@endphp

{{-- ============ TABS ============ --}}
<div class="mb-6 border-b border-slate-200">
    <nav class="flex flex-wrap gap-1 -mb-px">
        <a href="{{ route('products.index', ['tab' => 'products']) }}"
           class="inline-flex items-center gap-2 px-5 py-3 text-sm font-semibold border-b-2 transition
                  {{ $activeTab === 'products'
                        ? 'border-[#E31E24] text-[#E31E24]'
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
            <span class="w-4 h-4">
                @include('partials.icon', ['name' => 'box'])
            </span>
            Products
            <span class="px-2 py-0.5 rounded-full text-xs font-bold
                         {{ $activeTab === 'products' ? 'bg-red-100 text-[#E31E24]' : 'bg-slate-100 text-slate-500' }}">
                {{ $products->count() }}
            </span>
        </a>

        <a href="{{ route('products.index', ['tab' => 'recipes']) }}"
           class="inline-flex items-center gap-2 px-5 py-3 text-sm font-semibold border-b-2 transition
                  {{ $activeTab === 'recipes'
                        ? 'border-[#E31E24] text-[#E31E24]'
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
            <span class="w-4 h-4">
                @include('partials.icon', ['name' => 'book'])
            </span>
            Product Recipes
            <span class="px-2 py-0.5 rounded-full text-xs font-bold
                         {{ $activeTab === 'recipes' ? 'bg-red-100 text-[#E31E24]' : 'bg-slate-100 text-slate-500' }}">
                {{ $productIngredients->count() }}
            </span>
        </a>
    </nav>
</div>

@if ($activeTab === 'products')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Products</h2>
            <p class="text-sm text-slate-500 mt-0.5">{{ $products->count() }} product(s)</p>
        </div>
        <a href="{{ route('products.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Product
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @if ($products->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr class="text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3 font-semibold">Product</th>
                            <th class="px-6 py-3 font-semibold">Price</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($products as $product)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    @if ($product->image_path)
                                        <img src="{{ asset('storage/'.$product->image_path) }}"
                                             alt="{{ $product->product_name }}"
                                             class="mb-2 h-16 w-16 rounded-lg border border-slate-200 object-contain">
                                    @endif
                                    <div class="font-semibold text-slate-800">{{ $product->product_name }}</div>
                                    <div class="text-xs text-slate-500">
                                        #{{ str_pad($product->product_id, 3, '0', STR_PAD_LEFT) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-700">
                                    &#8369;{{ number_format($product->price, 2) }}
                                </td>
                                <td class="px-6 py-4">
                                    @if ($product->status === 'Active')
                                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('products.edit', $product) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 24H5.25A2.25 2.25 0 0 1 3 21.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                        </svg>
                                        Edit
                                    </a>
                                    <form action="{{ route('products.destroy', $product) }}" method="POST"
                                          onsubmit="return confirm('Delete this product?');"
                                          class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200 transition">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="px-6 py-20 text-center">
            <div class="text-5xl mb-3">📦</div>
            <p class="font-bold text-slate-700">No products yet</p>
            <p class="text-sm text-slate-500 mt-1 mb-6">Add your first product to the menu.</p>
            <a href="{{ route('products.create') }}"
               class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                Add First Product
            </a>
        </div>
    @endif
    </div>
</div>
@endif

@if ($activeTab === 'recipes')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Product Recipes</h2>
            <p class="text-sm text-slate-500 mt-0.5">{{ $productIngredients->count() }} recipe(s)</p>
        </div>
        <a href="{{ route('product-ingredients.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Recipe Ingredient
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @if ($productIngredients->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr class="text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3 font-semibold">Product</th>
                            <th class="px-6 py-3 font-semibold">Ingredient</th>
                            <th class="px-6 py-3 font-semibold">Quantity Required</th>
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($productIngredients as $recipe)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                    #{{ str_pad($recipe->product_ingredient_id, 3, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-800">
                                    {{ $recipe->product->product_name ?? 'Unknown Product' }}
                                </td>
                                <td class="px-6 py-4 text-slate-700">
                                    {{ $recipe->ingredient->ingredient_name ?? 'Unknown Ingredient' }}
                                    ({{ $recipe->ingredient->unit ?? '' }})
                                </td>
                                <td class="px-6 py-4 text-slate-700">
                                    {{ number_format($recipe->quantity_required, 2) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('product-ingredients.edit', $recipe) }}"
                                           class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                            Edit
                                        </a>
                                        <form action="{{ route('product-ingredients.destroy', $recipe) }}" method="POST"
                                              onsubmit="return confirm('Remove this ingredient?');"
                                              class="inline">
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
                <div class="text-5xl mb-3">📋</div>
                <p class="font-bold text-slate-700">No recipes yet</p>
                <p class="text-sm text-slate-500 mt-1 mb-6">Add ingredients to a product to create a recipe.</p>
                <a href="{{ route('product-ingredients.create') }}"
                   class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                    Add First Recipe
                </a>
            </div>
        @endif
    </div>
@endif

@endsection