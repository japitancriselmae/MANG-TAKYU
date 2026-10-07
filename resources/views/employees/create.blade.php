@extends('layouts.app')

@section('title', 'Add Employee')
@section('page-title', 'Add Employee')
@section('page-subtitle', 'Create a new employee record.')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('employees.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-sm font-semibold text-slate-700 mb-2">First Name</label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}"
                           required maxlength="255"
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl
                                  focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-semibold text-slate-700 mb-2">Last Name</label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}"
                           required maxlength="255"
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl
                                  focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                </div>
            </div>

            <div>
                <label for="role_id" class="block text-sm font-semibold text-slate-700 mb-2">Role</label>
                <select name="role_id" id="role_id" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    <option value="">Select Role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->role_id }}" {{ old('role_id') == $role->role_id ? 'selected' : '' }}>
                            {{ $role->role_name }}
                        </option>
                    @endforeach
                </select>
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
                    Save Employee
                </button>
                <a href="{{ route('employees.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold
                          hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>

@endsection