@extends('layouts.app')

@section('title', 'Employee Details')
@section('page-title', 'Employee Details')
@section('page-subtitle', 'View employee information.')

@section('content')

<div class="max-w-3xl">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <div class="flex items-start gap-5 mb-6">
            <div class="w-16 h-16 rounded-2xl bg-[#0B0B0B] text-[#F5A623] flex items-center justify-center font-extrabold text-2xl">
                {{ strtoupper(substr($employee->first_name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-800">
                    {{ $employee->first_name }} {{ $employee->last_name }}
                </h2>
                @if ($employee->role)
                    <span class="inline-block mt-1 px-3 py-1 rounded-full text-xs font-bold bg-[#E31E24]/10 text-[#E31E24]">
                        {{ $employee->role->role_name }}
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-5 border-t border-slate-100">
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Employee ID</div>
                <div class="text-lg font-bold text-slate-800">
                    #{{ str_pad($employee->employee_id, 4, '0', STR_PAD_LEFT) }}
                </div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Status</div>
                <div class="text-lg font-bold {{ $employee->status === 'Active' ? 'text-emerald-600' : 'text-slate-500' }}">
                    {{ $employee->status }}
                </div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">First Name</div>
                <div class="text-lg font-semibold text-slate-800">{{ $employee->first_name }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Last Name</div>
                <div class="text-lg font-semibold text-slate-800">{{ $employee->last_name }}</div>
            </div>
        </div>

        <div class="flex gap-3 mt-8">
            <a href="{{ route('employees.edit', $employee) }}"
               class="px-5 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
                Edit Employee
            </a>
            <a href="{{ route('employees.index') }}"
               class="px-5 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">
                Back to List
            </a>
        </div>
    </div>
</div>

@endsection