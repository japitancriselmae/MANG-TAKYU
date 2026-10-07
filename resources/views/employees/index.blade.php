@extends('layouts.app')

@section('title', 'Employees')
@section('page-title', 'Employees')
@section('page-subtitle', 'Manage Mang Takyu employees and their roles.')

@section('content')

@php
    $authRole = auth()->user()->employee?->role?->role_name;
    $canManage = $authRole === 'Manager';
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Employee List</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $employees->count() }} employee(s)</p>
    </div>

    @if ($canManage)
        <a href="{{ route('employees.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
                  text-white font-semibold shadow-lg shadow-red-500/20 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Employee
        </a>
    @endif
</div>

@if (!$canManage)
    <div class="mb-5 rounded-xl bg-slate-100 border border-slate-200 px-4 py-3 text-sm text-slate-600">
        You can <strong>view</strong> employees, but only the <strong>Manager</strong> can add, edit, or delete them.
    </div>
@endif

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    @if ($employees->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">ID</th>
                        <th class="px-6 py-3 font-semibold">Name</th>
                        <th class="px-6 py-3 font-semibold">Role</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($employees as $employee)
                        @php
                            $roleName = $employee->role->role_name ?? '';
                            $isAdmin  = $roleName === 'Admin';
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                #{{ str_pad($employee->employee_id, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-[#0B0B0B] text-[#F5A623] flex items-center justify-center font-bold text-sm">
                                        {{ strtoupper(substr($employee->first_name, 0, 1)) }}
                                    </div>
                                    <div class="font-semibold text-slate-800">
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $roleColors = [
                                        'Admin'         => 'bg-purple-100 text-purple-700',
                                        'Manager'       => 'bg-[#E31E24]/10 text-[#E31E24]',
                                        'Cashier'       => 'bg-blue-100 text-blue-700',
                                        'Kitchen Staff' => 'bg-amber-100 text-amber-700',
                                    ];
                                    $rc = $roleColors[$roleName] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold {{ $rc }}">
                                    {{ $roleName ?: 'No Role' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($employee->status === 'Active')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('employees.show', $employee) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        View
                                    </a>

                                    @if ($canManage && !$isAdmin)
                                        <a href="{{ route('employees.edit', $employee) }}"
                                           class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                            Edit
                                        </a>

                                        <form action="{{ route('employees.destroy', $employee) }}" method="POST"
                                              onsubmit="return confirm('Delete this employee?');">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200 transition">
                                                Delete
                                            </button>
                                        </form>
                                    @endif

                                    @if ($isAdmin)
                                        <span class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-100 text-purple-700 cursor-not-allowed inline-flex items-center gap-1"
                                              title="Protected Admin employee record">
                                            🔒 Locked
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-20 text-center">
            <div class="text-5xl mb-3">👥</div>
            <p class="font-bold text-slate-700">No employees yet</p>
            <p class="text-sm text-slate-500 mt-1 mb-6">Add your first employee to get started.</p>
            @if ($canManage)
                <a href="{{ route('employees.create') }}"
                   class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                    Add First Employee
                </a>
            @endif
        </div>
    @endif
</div>

@endsection