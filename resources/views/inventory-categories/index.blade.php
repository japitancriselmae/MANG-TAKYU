@extends('layouts.app')

@section('title', 'Inventory categories')
@section('page-title', 'Categories')
@section('page-subtitle', 'Group ingredients and see the stock each category contains.')

@section('content')
    <div class="flex flex-col gap-6">
        @if ($errors->has('category'))
            <p role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('category') }}</p>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Inventory categories</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $categories->total() }} categories</p>
            </div>
            <a href="{{ route('inventory-categories.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#E31E24] px-5 py-3 text-sm font-semibold text-white hover:bg-red-700">Add category</a>
        </div>

        <form method="GET" action="{{ route('inventory-categories.index') }}" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
            <label class="sr-only" for="category-search">Search categories</label>
            <input id="category-search" type="search" name="search" value="{{ request('search') }}" placeholder="Search category names" class="min-w-0 flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-[#E31E24] focus:ring-[#E31E24]/20">
            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Search</button>
            @if (request()->filled('search'))
                <a href="{{ route('inventory-categories.index') }}" class="rounded-xl border border-slate-200 px-5 py-2.5 text-center text-sm font-semibold text-slate-600">Clear</a>
            @endif
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Category</th>
                            <th class="px-5 py-4">Stock items</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($categories as $category)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <a class="font-semibold text-slate-900 hover:text-[#E31E24]" href="{{ route('inventory-categories.show', $category) }}">{{ $category->name }}</a>
                                    <p class="mt-1 max-w-xl truncate text-xs text-slate-500">{{ $category->description ?: 'No description' }}</p>
                                </td>
                                <td class="px-5 py-4 tabular-nums text-slate-700">{{ $category->ingredients_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('inventory-categories.show', $category) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">View items</a>
                                        <a href="{{ route('inventory-categories.edit', $category) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">Edit</a>
                                        @if ($category->ingredients_count === 0)
                                            <form action="{{ route('inventory-categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete this category?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-16 text-center text-slate-500">No categories match your search.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($categories->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $categories->links() }}</div>
            @endif
        </div>
    </div>
@endsection
