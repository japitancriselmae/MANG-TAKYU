@extends('layouts.app')

@section('title', 'Add Product')
@section('page-title', 'Add Product')
@section('page-subtitle', 'Create a new product.')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('products.store') }}" class="space-y-5" enctype="multipart/form-data">
            @csrf

            <div>
                <label for="product_name" class="block text-sm font-semibold text-slate-700 mb-2">
                    Product Name
                </label>
                <input type="text" name="product_name" id="product_name"
                       value="{{ old('product_name') }}" required maxlength="255"
                       placeholder="1pc Chicken with Rice"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div>
                <label for="price" class="block text-sm font-semibold text-slate-700 mb-2">
                    Price (₱)
                </label>
                <input type="number" name="price" id="price" value="{{ old('price') }}"
                       min="0" step="0.01" required placeholder="0.00"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div>
                <label for="image" class="mb-2 block text-sm font-semibold text-slate-700">Product Photo</label>
                <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
                       class="block w-full rounded-xl border border-slate-200 p-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-red-50 file:px-3 file:py-2 file:font-semibold file:text-[#E31E24]">
                <p class="mt-1 text-xs text-slate-500">Optional · JPG, PNG or WebP · maximum 5 MB</p>
                <img id="productImagePreview" class="mt-3 hidden max-h-56 rounded-xl border border-slate-200 object-contain" alt="Product photo preview">
                @error('image') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                <select name="status" id="status" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    <option value="Active"   {{ old('status', 'Active') === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ old('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="flex gap-3 pt-3">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                               shadow-lg shadow-red-500/20 transition">
                    Save Product
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

@push('scripts')
<script>
document.getElementById('image').addEventListener('change', (event) => {
    const preview = document.getElementById('productImagePreview');
    const file = event.target.files[0];
    if (!file) {
        preview.classList.add('hidden');
        preview.removeAttribute('src');
        return;
    }

    preview.src = URL.createObjectURL(file);
    preview.classList.remove('hidden');
});
</script>
@endpush

@endsection