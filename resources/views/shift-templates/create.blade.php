@extends('layouts.app')

@section('title', 'Create Shift Template')
@section('page-title', 'Create Shift Template')
@section('page-subtitle', 'Add a new shift template.')

@section('content')

<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('shift-templates.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-2">Shift Name</label>
                <input type="text" name="name" id="name" required maxlength="255"
                       value="{{ old('name') }}"
                       placeholder="e.g. Morning Shift"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_time" class="block text-sm font-semibold text-slate-700 mb-2">Start Time</label>
                    <input type="time" name="start_time" id="start_time"
                           value="{{ old('start_time', '08:00') }}"
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl
                                  focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    @error('start_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="end_time" class="block text-sm font-semibold text-slate-700 mb-2">End Time</label>
                    <input type="time" name="end_time" id="end_time"
                           value="{{ old('end_time', '16:00') }}"
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl
                                  focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    @error('end_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="required_employee_count" class="block text-sm font-semibold text-slate-700 mb-2">Required Employees</label>
                <input type="number" name="required_employee_count" id="required_employee_count" required min="0"
                       value="{{ old('required_employee_count', 1) }}"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                @error('required_employee_count')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-slate-500">Number of employees needed for this shift.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center">
                        <input type="hidden" name="is_overnight" value="0">
                        <input id="is_overnight" name="is_overnight" type="checkbox" value="1"
                               @checked(old('is_overnight'))
                               class="rounded border-slate-300 text-[#E31E24] focus:ring-[#E31E24]">
                        <label for="is_overnight" class="ml-2 text-sm text-slate-700">Overnight Shift</label>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Shift continues into the next day.</p>
                </div>
                <div>
                    <div class="flex items-center">
                        <input type="hidden" name="is_active" value="0">
                        <input id="is_active" name="is_active" type="checkbox" value="1"
                               @checked(old('is_active', true))
                               class="rounded border-slate-300 text-[#E31E24] focus:ring-[#E31E24]">
                        <label for="is_active" class="ml-2 text-sm text-slate-700">Active</label>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Include in automatic schedule generation.</p>
                </div>
            </div>

            <div class="flex gap-3 pt-3">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                               shadow-lg shadow-red-500/20 transition">
                    Create Template
                </button>
                <a href="{{ route('shift-templates.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold
                          hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>

@endsection
