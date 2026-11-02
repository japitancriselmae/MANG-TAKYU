@extends('layouts.app')

@section('title', 'Inventory')
@section('page-title', 'Inventory')
@section('page-subtitle', 'Track stock levels and manage ingredients.')

@section('content')

@php
    $isStockTab = $activeTab === 'stock';
    $isIngredientsTab = $activeTab === 'ingredients';
@endphp

<div class="mb-6 border-b border-slate-200">
    <nav class="flex flex-wrap gap-1 -mb-px">
        <a href="{{ route('inventories.index', ['tab' => 'stock']) }}"
           class="inline-flex items-center gap-2 px-5 py-3 text-sm font-semibold border-b-2 transition
                  {{ $isStockTab
                        ? 'border-[#E31E24] text-[#E31E24]'
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
            <span class="w-4 h-4">
                @include('partials.icon', ['name' => 'archive'])
            </span>
            Stock Items
            <span class="px-2 py-0.5 rounded-full text-xs font-bold
                         {{ $isStockTab ? 'bg-red-100 text-[#E31E24]' : 'bg-slate-100 text-slate-500' }}">
                {{ $inventories->count() }}
            </span>
        </a>

        <a href="{{ route('inventory-categories.index') }}"
           class="inline-flex items-center gap-2 px-5 py-3 text-sm font-semibold text-slate-500 transition hover:text-[#E31E24]">
            Categories
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold">{{ $categories->count() }}</span>
        </a>
        <a href="{{ route('inventories.index', ['tab' => 'ingredients']) }}"
           class="inline-flex items-center gap-2 px-5 py-3 text-sm font-semibold border-b-2 transition
                  {{ $isIngredientsTab
                        ? 'border-[#E31E24] text-[#E31E24]'
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
            <span class="w-4 h-4">
                @include('partials.icon', ['name' => 'box'])
            </span>
            Ingredients
            <span class="px-2 py-0.5 rounded-full text-xs font-bold
                         {{ $isIngredientsTab ? 'bg-red-100 text-[#E31E24]' : 'bg-slate-100 text-slate-500' }}">
                {{ $ingredients->count() }}
            </span>
        </a>


    </nav>
</div>

@if ($isStockTab)

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Stock Items</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $inventories->total() }} record(s)</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('inventories.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
                  text-white font-semibold shadow-lg shadow-red-500/20 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Stock Item
        </a>
        <a href="{{ route('ingredients.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700
                  text-white font-semibold shadow-lg shadow-amber-500/20 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Ingredient
        </a>
    </div>
</div>

<form method="GET" action="{{ route('inventories.index') }}" class="mb-5 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-5">
    <input type="hidden" name="tab" value="stock">
    <label class="sr-only" for="inventory-search">Search stock</label>
    <input id="inventory-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search ingredient or SKU"
           class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#E31E24] focus:ring-[#E31E24]/20">
    <label class="sr-only" for="inventory-category-filter">Category</label>
    <select id="inventory-category-filter" name="category" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm">
        <option value="">All categories</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(($filters['category'] ?? '') == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <label class="sr-only" for="inventory-stock-filter">Stock level</label>
    <select id="inventory-stock-filter" name="stock_level" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm">
        <option value="">All stock levels</option>
        <option value="in-stock" @selected(($filters['stock_level'] ?? '') === 'in-stock')>In stock</option>
        <option value="low-stock" @selected(($filters['stock_level'] ?? '') === 'low-stock')>Low stock</option>
        <option value="out-of-stock" @selected(($filters['stock_level'] ?? '') === 'out-of-stock')>Out of stock</option>
    </select>
    <label class="sr-only" for="inventory-sort">Sort stock</label>
    <select id="inventory-sort" name="sort" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm">
        <option value="name" @selected(($filters['sort'] ?? 'name') === 'name')>Name (A–Z)</option>
        <option value="quantity" @selected(($filters['sort'] ?? '') === 'quantity')>Lowest stock</option>
        <option value="updated" @selected(($filters['sort'] ?? '') === 'updated')>Recently updated</option>
    </select>
    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Apply filters</button>
</form>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    @if ($inventories->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">Ingredient</th>
                        <th class="px-6 py-3 font-semibold">Category / SKU</th>
                        <th class="px-6 py-3 font-semibold">Unit</th>
                        <th class="px-6 py-3 font-semibold">Current Qty</th>
                        <th class="px-6 py-3 font-semibold">Minimum Stock</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($inventories as $item)
                        @php
                            $isOutOfStock = $item->isOutOfStock();
                            $isLow = $item->isLowStock();
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                {{ $item->ingredient->ingredient_name ?? 'Unknown Ingredient' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="block text-slate-700">{{ $item->ingredient->inventoryCategory->name ?? 'Uncategorized' }}</span>
                                <span class="font-mono text-xs text-slate-400">{{ $item->ingredient->sku ?: '—' }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $item->ingredient->unit ?? '' }}
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-800">
                                {{ number_format($item->quantity, 2) }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ number_format($item->minimum_stock, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                @if ($isOutOfStock)
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-[#E31E24]">Out of Stock</span>
                                @elseif ($isLow)
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Low Stock</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Sufficient</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('inventories.show', $item) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        View
                                    </a>
                                    <a href="{{ route('inventories.edit', $item) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                        Edit
                                    </a>

                                    <form action="{{ route('inventories.destroy', $item) }}" method="POST"
                                          onsubmit="return confirm('Remove this stock item?');">
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
            <p class="font-bold text-slate-700">No stock items yet</p>
            <p class="text-sm text-slate-500 mt-1 mb-6">Add ingredients to inventory to track stock levels.</p>
            <a href="{{ route('inventories.create') }}"
               class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                Add First Stock Item
            </a>
        </div>
    @endif
    @if ($inventories->hasPages())
        <div class="border-t border-slate-100 px-6 py-4">{{ $inventories->links() }}</div>
    @endif
</div>

@elseif ($isIngredientsTab)

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Ingredients</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $ingredients->count() }} ingredient(s)</p>
    </div>
    <a href="{{ route('ingredients.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
              text-white font-semibold shadow-lg shadow-red-500/20 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add Ingredient
    </a>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    @if ($ingredients->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">Ingredient Name</th>
                        <th class="px-6 py-3 font-semibold">Unit</th>
                        <th class="px-6 py-3 font-semibold">Minimum Stock</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($ingredients as $ingredient)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                {{ $ingredient->ingredient_name }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $ingredient->unit }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ number_format($ingredient->minimum_stock, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                @if ($ingredient->status === 'Active')
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Active</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('ingredients.edit', $ingredient) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-20 text-center">
            <div class="text-5xl mb-3">🧂</div>
            <p class="font-bold text-slate-700">No ingredients yet</p>
            <p class="text-sm text-slate-500 mt-1 mb-6">Start by adding your first ingredient.</p>
            <a href="{{ route('ingredients.create') }}"
               class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                Add First Ingredient
            </a>
        </div>
    @endif
</div>

@else

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Product Recipes</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $productIngredients->count() }} recipe(s)</p>
    </div>
    <a href="{{ route('product-ingredients.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
              text-white font-semibold shadow-lg shadow-red-500/20 transition">
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
                        <th class="px-6 py-3 font-semibold">ID</th>
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
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ number_format($recipe->quantity_required, 2) }}
                                {{ $recipe->ingredient->unit ?? '' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('product-ingredients.show', $recipe) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        View
                                    </a>
                                    <a href="{{ route('product-ingredients.edit', $recipe) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('product-ingredients.destroy', $recipe) }}" method="POST"
                                          onsubmit="return confirm('Remove this ingredient?');">
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
            <div class="text-5xl mb-3">📖</div>
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
