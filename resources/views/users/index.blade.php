@extends('layouts.app')

@section('title', 'User Accounts')
@section('page-title', 'User Accounts')
@section('page-subtitle', 'Manage login accounts.')

@section('content')

@php
    $authRole = auth()->user()->employee?->role?->role_name;

    // Which roles can this user manage?
    $manageable = match ($authRole) {
        'Admin'   => ['Manager'],
        'Manager' => ['Cashier', 'Kitchen Staff'],
        default   => [],
    };

    $canCreate = !empty($manageable);
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Login Accounts</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $users->count() }} user(s)</p>
    </div>

    @if ($canCreate)
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
                  text-white font-semibold shadow-lg shadow-red-500/20 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Create User Account
        </a>
    @endif
</div>

{{-- Role scope notice --}}
<div class="mb-5 rounded-xl bg-slate-100 border border-slate-200 px-4 py-3 text-sm text-slate-700">
    @if ($authRole === 'Admin')
        <strong>You are an Admin.</strong> You can create, edit, and delete <strong>Manager</strong> accounts.
        Staff accounts are managed by their Manager.
    @elseif ($authRole === 'Manager')
        <strong>You are a Manager.</strong> You can create, edit, and delete <strong>Staff</strong> accounts (Cashier, Kitchen Staff).
    @endif
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    @if ($users->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">ID</th>
                        <th class="px-6 py-3 font-semibold">Name</th>
                        <th class="px-6 py-3 font-semibold">Email</th>
                        <th class="px-6 py-3 font-semibold">Role</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $user)
                        @php
                            $rn = $user->employee?->role?->role_name ?? 'No Role';
                            $roleColors = [
                                'Manager'       => 'bg-[#E31E24]/10 text-[#E31E24]',
                                'Cashier'       => 'bg-blue-100 text-blue-700',
                                'Kitchen Staff' => 'bg-amber-100 text-amber-700',
                            ];
                            $rc = $roleColors[$rn] ?? 'bg-slate-100 text-slate-700';

                            // Can the current user manage this row?
                            $canManageThis = in_array($rn, $manageable) && $user->id !== auth()->id();
                            $isMe = $user->id === auth()->id();
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                #{{ str_pad($user->id, 3, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-[#0B0B0B] text-[#F5A623] flex items-center justify-center font-bold text-sm">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="font-semibold text-slate-800">
                                        {{ $user->name }}
                                        @if ($isMe)
                                            <span class="text-xs text-slate-400 font-normal">(you)</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-700">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold {{ $rc }}">
                                    {{ $rn }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if (($user->employee?->status ?? 'Active') === 'Active')
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
                                    <a href="{{ route('users.show', $user) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        View
                                    </a>

                                    @if ($canManageThis)
                                        <a href="{{ route('users.edit', $user) }}"
                                           class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                            Edit
                                        </a>

                                        <form action="{{ route('users.destroy', $user) }}" method="POST"
                                              onsubmit="return confirm('Delete this user account?');">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200 transition">
                                                Delete
                                            </button>
                                        </form>
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
            <div class="text-5xl mb-3">👤</div>
            <p class="font-bold text-slate-700">No user accounts yet</p>
            <p class="text-sm text-slate-500 mt-1 mb-6">Create login accounts.</p>
            @if ($canCreate)
                <a href="{{ route('users.create') }}"
                   class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                    Create First Account
                </a>
            @endif
        </div>
    @endif
</div>

@endsection