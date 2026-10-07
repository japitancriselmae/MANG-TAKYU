@extends('layouts.app')

@section('title', 'Role Details')
@section('page-title', 'Role Details')
@section('page-subtitle', 'View role information.')

@section('content')

<div class="max-w-3xl">

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8 mb-5">

        <div class="flex items-start gap-5 mb-6">
            <div class="w-16 h-16 rounded-2xl bg-[#0B0B0B] text-[#F5A623] flex items-center justify-center font-extrabold text-2xl">
                {{ strtoupper(substr($role->role_name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-800">{{ $role->role_name }}</h2>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mt-1">
                    Role #{{ str_pad($role->role_id, 3, '0', STR_PAD_LEFT) }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-5 border-t border-slate-100">
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Role ID</div>
                <div class="text-lg font-bold text-slate-800">
                    #{{ str_pad($role->role_id, 3, '0', STR_PAD_LEFT) }}
                </div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Employees</div>
                <div class="text-lg font-bold text-slate-800">
                    {{ $role->employees_count }} assigned
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-8">
            <a href="{{ route('roles.edit', $role) }}"
               class="px-5 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
                Edit Role
            </a>
            <a href="{{ route('roles.index') }}"
               class="px-5 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">
                Back to List
            </a>
        </div>
    </div>

</div>

@endsection