@extends('layouts.app')

@section('title', 'Schedules')
@section('page-title', 'Schedules')
@section('page-subtitle', 'Manage employee work schedules.')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Employee Schedules</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $schedules->count() }} schedule(s)</p>
    </div>
    <a href="{{ route('schedules.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
              text-white font-semibold shadow-lg shadow-red-500/20 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add Schedule
    </a>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    @if ($schedules->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">Employee</th>
                        <th class="px-6 py-3 font-semibold">Date</th>
                        <th class="px-6 py-3 font-semibold">Start</th>
                        <th class="px-6 py-3 font-semibold">End</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($schedules as $schedule)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-[#0B0B0B] text-[#F5A623] flex items-center justify-center font-bold text-sm">
                                        {{ strtoupper(substr($schedule->employee->first_name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-800">
                                            {{ $schedule->employee->first_name }} {{ $schedule->employee->last_name }}
                                        </div>
                                        <div class="text-xs text-slate-500">ID: {{ $schedule->employee_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-700 font-medium">
                                {{ \Carbon\Carbon::parse($schedule->schedule_date)->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                            </td>
                            <td class="px-6 py-4">
                                @if ($schedule->status === 'Active')
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
                                    <a href="{{ route('schedules.edit', $schedule) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('schedules.destroy', $schedule) }}" method="POST"
                                          onsubmit="return confirm('Delete this schedule?');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-20 text-center">
            <div class="text-5xl mb-3">📅</div>
            <p class="font-bold text-slate-700">No schedules yet</p>
            <p class="text-sm text-slate-500 mt-1 mb-6">Add the first employee schedule.</p>
            <a href="{{ route('schedules.create') }}"
               class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                Add Schedule
            </a>
        </div>
    @endif
</div>

@endsection