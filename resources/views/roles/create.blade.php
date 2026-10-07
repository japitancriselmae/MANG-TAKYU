@extends('layouts.app')

@section('title', 'Add Role')
@section('page-title', 'Add Role')
@section('page-subtitle', 'Create a new employee role.')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('roles.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="role_name" class="block text-sm font-semibold text-slate-700 mb-2">
                    Role Name
                </label>
                <input type="text" name="role_name" id="role_name"
                       value="{{ old('role_name') }}" required maxlength="255"
                       placeholder="Cashier"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                <p class="text-xs text-slate-500 mt-2">
                    Common roles: Manager, Cashier, Kitchen Staff
                </p>
            </div>

            <div class="flex gap-3 pt-3">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                               shadow-lg shadow-red-500/20 transition">
                    Save Role
                </button>
                <a href="{{ route('roles.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold
                          hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>

@endsection