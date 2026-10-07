@extends('layouts.app')

@section('title', 'User Account Details')
@section('page-title', 'User Account')
@section('page-subtitle', 'Login account information.')

@section('content')

<div class="max-w-3xl">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <div class="flex items-start gap-5 mb-6">
            <div class="w-16 h-16 rounded-2xl bg-[#0B0B0B] text-[#F5A623] flex items-center justify-center font-extrabold text-2xl">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-800">{{ $user->name }}</h2>
                @if ($user->employee?->role)
                    <span class="inline-block mt-1 px-3 py-1 rounded-full text-xs font-bold bg-[#E31E24]/10 text-[#E31E24]">
                        {{ $user->employee->role->role_name }}
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-5 border-t border-slate-100">
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">User ID</div>
                <div class="text-lg font-bold text-slate-800">
                    #{{ str_pad($user->id, 3, '0', STR_PAD_LEFT) }}
                </div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Employee ID</div>
                <div class="text-lg font-bold text-slate-800">
                    {{ $user->employee_id ? '#' . str_pad($user->employee_id, 4, '0', STR_PAD_LEFT) : '—' }}
                </div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Email</div>
                <div class="text-lg font-semibold text-slate-800">{{ $user->email }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Account Created</div>
                <div class="text-lg font-semibold text-slate-800">
                    {{ $user->created_at?->format('M d, Y') }}
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-8">
            <a href="{{ route('users.edit', $user) }}"
               class="px-5 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
                Edit Account
            </a>
            <a href="{{ route('users.index') }}"
               class="px-5 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">
                Back to List
            </a>
        </div>
    </div>
</div>

@endsection