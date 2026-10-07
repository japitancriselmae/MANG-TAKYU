@extends('layouts.app')

@section('title', 'Ingredients')
@section('page-title', 'Ingredients')
@section('page-subtitle', 'Manage ingredients used in Mang Takyu products.')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Ingredient List</h2>
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
                        <th class="px-6 py-3 font-semibold">ID</th>
                        <th class="px-6 py-3 font-semibold">Ingredient</th>
                        <th class="px-6 py-3 font-semibold">Unit</th>
                        <th class="px-6 py-3 font-semibold">Minimum Stock</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($ingredients as $ingredient)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                #{{ str_pad($ingredient->ingredient_id, 3, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                {{ $ingredient->ingredient_name }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $ingredient->unit }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ number_format($ingredient->minimum_stock, 2) }} {{ $ingredient->unit }}
                            </td>
                            <td class="px-6 py-4">
                                @if ($ingredient->status === 'Active')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('ingredients.show', $ingredient->ingredient_id) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        View
                                    </a>
                                    <a href="{{ route('ingredients.edit', $ingredient->ingredient_id) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('ingredients.destroy', $ingredient->ingredient_id) }}" method="POST"
                                          onsubmit="return confirm('Delete this ingredient?');">
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
            <div class="text-5xl mb-3">🌿</div>
            <p class="font-bold text-slate-700">No ingredients yet</p>
            <p class="text-sm text-slate-500 mt-1 mb-6">Add your first ingredient to get started.</p>
            <a href="{{ route('ingredients.create') }}"
               class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                Add First Ingredient
            </a>
        </div>
    @endif
</div>

@endsection