<form action="{{ $action }}" method="POST" class="space-y-5">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    <label class="block text-sm font-semibold text-slate-700">
        Category name
        <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" maxlength="100" required
               class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-[#E31E24] focus:ring-[#E31E24]/20">
    </label>

    <label class="block text-sm font-semibold text-slate-700">
        Description <span class="font-normal text-slate-400">Optional</span>
        <textarea name="description" rows="3" maxlength="2000"
                  class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-[#E31E24] focus:ring-[#E31E24]/20">{{ old('description', $category->description ?? '') }}</textarea>
    </label>

    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="rounded-xl bg-[#E31E24] px-5 py-3 text-sm font-semibold text-white hover:bg-red-700">
            {{ $method === 'PUT' ? 'Save changes' : 'Create category' }}
        </button>
        <a href="{{ route('inventory-categories.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Cancel
        </a>
    </div>
</form>
