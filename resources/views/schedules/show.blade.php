@extends('layouts.app')

@section('title', 'Schedule Details')
@section('page-title', 'Schedule Details')
@php $scheduleDate = $schedule->schedule_date->format('M d, Y'); @endphp
@section('page-subtitle', $schedule->employee->first_name.' '.$schedule->employee->last_name.' · '.$scheduleDate)

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Schedule Details</h2>
        <p class="text-sm text-slate-500 mt-0.5">
            {{ $schedule->employee->first_name }} {{ $schedule->employee->last_name }}
            <span class="ml-2 text-xs font-semibold text-slate-400">ID: {{ $schedule->employee_id }}</span>
        </p>
    </div>
    <div class="flex gap-2">
        @php $weekStartDate = $schedule->schedule_date->copy()->startOfWeek()->toDateString(); @endphp
        <a href="{{ route('schedules.index', ['week_start_date' => $weekStartDate]) }}"
           class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">
            Back to Schedule
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Employee</h3>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#0B0B0B] text-[#F5A623] flex items-center justify-center text-sm font-bold">
                    {{ strtoupper(substr($schedule->employee->first_name, 0, 1)) }}
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-800">{{ $schedule->employee->first_name }} {{ $schedule->employee->last_name }}</div>
                    <div class="text-xs text-slate-500">{{ $schedule->employee->role?->role_name ?? 'No Role' }}</div>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Date</h3>
            @php $formattedDate = $schedule->schedule_date->format('l, M d, Y'); @endphp
                <p class="text-lg font-bold text-slate-800">
                    {{ $formattedDate }}
                </p>
        </div>

        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Shift Time</h3>
            @if ($schedule->start_time && $schedule->end_time)
                @php
                    $startTime = \Carbon\Carbon::parse($schedule->start_time)->format('h:i A');
                    $endTime = \Carbon\Carbon::parse($schedule->end_time)->format('h:i A');
                @endphp
                <p class="text-lg font-bold text-slate-800">
                    {{ $startTime }} — {{ $endTime }}
                </p>
            @else
                <p class="text-lg font-bold text-slate-400">Rest Day</p>
            @endif
        </div>

        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Shift Template</h3>
            <p class="text-lg font-bold text-slate-800">
                {{ $schedule->shiftTemplate?->name ?? 'Rest Day' }}
            </p>
        </div>

        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Status</h3>
            @php
                $statusColors = [
                    'draft' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'approved' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'published' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                ];
                $statusColor = $statusColors[$schedule->schedule_status ?? 'draft'] ?? 'bg-slate-50 text-slate-700';
            @endphp
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusColor }}">
                {{ ucfirst($schedule->schedule_status ?? 'draft') }}
            </span>
        </div>

        <div class="col-span-full">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Actions</h3>
            <div class="flex gap-3">
                <a href="{{ route('schedules.edit', $schedule) }}"
                   class="px-5 py-2.5 rounded-xl bg-blue-100 text-blue-700 font-semibold hover:bg-blue-200 transition">
                    Edit Schedule
                </a>
                <form method="POST" action="{{ route('schedules.destroy', $schedule) }}"
                      onsubmit="return confirm('Delete this schedule? Attendance records prevent deletion.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-red-100 text-red-700 font-semibold hover:bg-red-200 transition">
                        Delete Schedule
                    </button>
                </form>

                @if ($schedule->schedule_status === 'draft')
                    <form method="POST" action="{{ route('schedules.approve', $schedule) }}"
                          class="inline"
                          onsubmit="return confirm('Approve this schedule?')">
                        @csrf
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-blue-100 text-blue-700 font-semibold hover:bg-blue-200 transition">
                            Approve Schedule
                        </button>
                    </form>
                @elseif ($schedule->schedule_status === 'approved')
                    <form method="POST" action="{{ route('schedules.publish', $schedule) }}"
                          class="inline"
                          onsubmit="return confirm('Publish this schedule?')">
                        @csrf
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-emerald-100 text-emerald-700 font-semibold hover:bg-emerald-200 transition">
                            Publish Schedule
                        </button>
                    </form>
                @endif

                <a href="{{ route('schedules.index', ['week_start_date' => $weekStartDate]) }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">
                    Back to List
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
