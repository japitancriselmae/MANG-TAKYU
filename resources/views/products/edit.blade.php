@extends('layouts.app')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')
@section('page-subtitle', 'Update product information.')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('products.update', $product) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label for="product_name" class="block text-sm font-semibold text-slate-700 mb-2">
                    Product Name
                </label>
                <input type="text" name="product_name" id="product_name"
                       value="{{ old('product_name', $product->product_name) }}" required maxlength="255"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div>
                <label for="price" class="block text-sm font-semibold text-slate-700 mb-2">
                    Price (₱)
                </label>
                <input type="number" name="price" id="price"
                       value="{{ old('price', $product->price) }}" min="0" step="0.01" required
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div>
                <label for="status" class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                <select name="status" id="status" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    <option value="Active"   {{ old('status', $product->status) === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ old('status', $product->status) === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="flex gap-3 pt-3">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                               shadow-lg shadow-red-500/20 transition">
                    Update Product
                </button>
                <a href="{{ route('products.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold
                          hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>

@endsection