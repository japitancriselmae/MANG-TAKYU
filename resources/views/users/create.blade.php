@extends('layouts.app')

@section('title', 'Create User Account')
@section('page-title', 'Create User Account')
@section('page-subtitle', 'Set login credentials for an employee.')

@section('content')

@php
    $authRole = auth()->user()->employee?->role?->role_name;
@endphp

<div class="max-w-2xl mx-auto">

    {{-- Role scope notice --}}
        <div class="mb-5 rounded-xl bg-slate-100 border border-slate-200 px-4 py-3 text-sm text-slate-700">
            @if ($authRole === 'Admin')
                <strong>You are an Admin.</strong> You can create <strong>Manager</strong> accounts only.
            @elseif ($authRole === 'Manager')
                <strong>You are a Manager.</strong> You can create <strong>Staff</strong> accounts only (Cashier, Kitchen Staff).
            @endif
        </div>

    @if ($employees->isEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 font-bold">!</div>
                <div>
                    <div class="font-bold text-amber-800">No employees available</div>
                        <p class="text-sm text-amber-700 mt-1">
                            @if ($authRole === 'Admin')
                                No Managers without accounts yet. Add a Manager employee first, then create their login.
                            @else
                                No Cashier or Kitchen Staff without accounts yet. Add them as Employees first.
                            @endif
                        </p>
                    @if ($authRole === 'Admin')
                        <a href="{{ route('employees.create') }}"
                           class="inline-block mt-3 px-4 py-2 rounded-lg bg-[#E31E24] hover:bg-red-700 text-white text-sm font-semibold transition">
                            Add Employee First
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
            <form method="POST" action="{{ route('users.store') }}" class="space-y-5" autocomplete="off">
                @csrf

                <input type="text"     name="fakeusernameremembered" style="display:none">
                <input type="password" name="fakepasswordremembered" style="display:none">

                <div>
                    <label for="employee_id" class="block text-sm font-semibold text-slate-700 mb-2">Employee</label>
                    <select name="employee_id" id="employee_id" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                                   focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                        <option value="">Select Employee</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->employee_id }}"
                                {{ old('employee_id') == $employee->employee_id ? 'selected' : '' }}>
                                {{ $employee->first_name }} {{ $employee->last_name }}
                                — {{ $employee->role->role_name ?? 'No Role' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email') }}" required maxlength="255"
                           autocomplete="off"
                           placeholder="Enter login email"
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl
                                  focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                    <input type="password" name="password" id="password" required
                           autocomplete="new-password"
                           placeholder="Enter a password (min 6 characters)"
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl
                                  focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           autocomplete="new-password"
                           placeholder="Re-enter the same password"
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl
                                  focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                </div>

                <div class="flex gap-3 pt-3">
                    <button type="submit"
                            class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                                   shadow-lg shadow-red-500/20 transition">
                        Create Account
                    </button>
                    <a href="{{ route('users.index') }}"
                       class="px-6 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold
                              hover:bg-slate-50 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    @endif
</div>

@endsection