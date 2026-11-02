@extends('layouts.app')

@section('title', 'Schedule')
@section('page-title', 'Schedule')
@section('page-subtitle', 'Plan and manage staff shifts by week.')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">Weekly schedule</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                {{ $weekStart->format('M j') }} – {{ $weekEnd->format('M j, Y') }}
            </h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('shift-templates.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Shift templates
            </a>
            <a href="{{ route('schedules.create') }}"
               class="inline-flex items-center justify-center rounded-xl bg-[#E31E24] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                Add schedule
            </a>
            <button type="button" data-open-schedule-modal
                    class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                Generate schedule
            </button>
        </div>
    </div>

    <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-label="Schedule filters">
        <form method="GET" action="{{ route('schedules.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto_auto] lg:items-end">
            <input type="hidden" name="week_start_date" value="{{ $weekStart->toDateString() }}">
            <div>
                <label for="employee_filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</label>
                <select id="employee_filter" name="employee_id"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
                    <option value="">All employees</option>
                    @foreach ($employeeOptions as $employee)
                        <option value="{{ $employee->employee_id }}" @selected(($validated['employee_id'] ?? '') == $employee->employee_id)>
                            {{ $employee->first_name }} {{ $employee->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="shift_filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Shift template</label>
                <select id="shift_filter" name="shift_template_id"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
                    <option value="">All shifts</option>
                    @foreach ($shiftTemplates as $template)
                        <option value="{{ $template->shift_template_id }}" @selected(($validated['shift_template_id'] ?? '') == $template->shift_template_id)>
                            {{ $template->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                Apply filters
            </button>
            @if ($filters !== [])
                <a href="{{ route('schedules.index', ['week_start_date' => $weekStart->toDateString()]) }}"
                   class="rounded-xl px-3 py-2.5 text-center text-sm font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">
                    Clear filters
                </a>
            @endif
        </form>
    </section>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex items-center gap-2" aria-label="Schedule week navigation">
            <a href="{{ route('schedules.index', array_merge($filters, ['week_start_date' => $weekStart->copy()->subWeek()->toDateString()])) }}"
               aria-label="Previous week"
               class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/>
                </svg>
            </a>
            <a href="{{ route('schedules.index', array_merge($filters, ['week_start_date' => now()->toDateString()])) }}"
               class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                This week
            </a>
            <a href="{{ route('schedules.index', array_merge($filters, ['week_start_date' => $weekStart->copy()->addWeek()->toDateString()])) }}"
               aria-label="Next week"
               class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>
                </svg>
            </a>
        </nav>

        <div class="flex flex-wrap items-center gap-2">
            @if ($scheduleStatus)
                @php
                    $statusClasses = [
                        'draft' => 'border-amber-200 bg-amber-50 text-amber-800',
                        'approved' => 'border-blue-200 bg-blue-50 text-blue-800',
                        'published' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                    ];
                @endphp
                <span class="rounded-full border px-3 py-1.5 text-xs font-bold {{ $statusClasses[$scheduleStatus] ?? 'border-slate-200 bg-slate-50 text-slate-700' }}">
                    {{ ucfirst($scheduleStatus) }}
                </span>
            @elseif ($scheduleActions)
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700">Mixed status</span>
            @endif

            @if ($scheduleActions && $hasDraftSchedules)
                <form method="POST" action="{{ route('schedules.approve', $scheduleActions) }}" onsubmit="return confirm('Approve all draft schedules for this week?')">
                    @csrf
                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                        Approve drafts
                    </button>
                </form>
            @endif
            @if ($scheduleActions && $hasApprovedSchedules)
                <form method="POST" action="{{ route('schedules.publish', $scheduleActions) }}" onsubmit="return confirm('Publish all approved schedules for this week?')">
                    @csrf
                    <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Publish approved
                    </button>
                </form>
            @endif
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-label="Weekly schedule grid">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1060px] border-collapse text-left">
                <thead>
                    <tr class="bg-slate-50">
                        <th scope="col" class="sticky left-0 z-10 w-56 border-b border-r border-slate-200 bg-slate-50 px-4 py-4 text-xs font-bold uppercase tracking-wide text-slate-500">
                            Employee <span class="ml-1 font-medium normal-case tracking-normal">({{ $employees->count() }})</span>
                        </th>
                        @foreach ($days as $day)
                            @php $isToday = $day['key'] === now()->toDateString(); @endphp
                            <th scope="col" class="min-w-[132px] border-b border-r border-slate-200 px-3 py-3 text-center last:border-r-0 {{ $isToday ? 'bg-red-50/70' : '' }}">
                                <span class="block text-[11px] font-bold uppercase tracking-wide {{ $isToday ? 'text-[#E31E24]' : 'text-slate-500' }}">
                                    {{ $day['weekday'] }}
                                </span>
                                <span class="mt-1 block text-sm font-bold text-slate-800">{{ $day['date']->format('M j') }}</span>
                                @if ($isToday)
                                    <span class="mt-1 inline-block rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700">Today</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($employees as $employee)
                        <tr class="align-top">
                            <th scope="row" class="sticky left-0 z-[1] w-56 border-r border-slate-200 bg-white px-4 py-4 text-left">
                                <span class="flex items-start gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-amber-300">
                                        {{ strtoupper(substr($employee->first_name, 0, 1)) }}
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-bold text-slate-800">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                        <span class="mt-0.5 block truncate text-xs font-medium text-slate-500">{{ $employee->role?->role_name ?? 'No role' }}</span>
                                        <span class="mt-1 block text-xs text-slate-400">{{ number_format($weeklyHoursByEmployee[$employee->employee_id] ?? 0, 1) }} scheduled hrs</span>
                                    </span>
                                </span>
                            </th>
                            @foreach ($days as $day)
                                @php $daySchedules = $employeeSchedules[$employee->employee_id][$day['key']] ?? []; @endphp
                                <td class="min-w-[132px] border-r border-slate-100 p-2 align-top last:border-r-0">
                                    <div class="flex min-h-[72px] flex-col gap-1.5">
                                        @forelse ($daySchedules as $shift)
                                            @php
                                                $isRestDay = ! $shift->start_time || ! $shift->end_time;
                                                $start = $shift->start_time ? \Carbon\Carbon::parse($shift->start_time)->format('g:i A') : null;
                                                $end = $shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('g:i A') : null;
                                                $shiftName = $shift->shiftTemplate?->name ?? ($isRestDay ? 'Rest day' : 'Shift');
                                                $shiftClasses = $isRestDay
                                                    ? 'border-amber-200 bg-amber-50 text-amber-900 hover:bg-amber-100'
                                                    : 'border-blue-200 bg-blue-50 text-blue-900 hover:bg-blue-100';
                                            @endphp
                                            <a href="{{ route('schedules.show', $shift) }}"
                                               title="{{ $shiftName }}{{ $start ? ': '.$start.' – '.$end : '' }}"
                                               class="block rounded-xl border px-2.5 py-2 transition {{ $shiftClasses }}">
                                                <span class="block truncate text-xs font-bold">{{ $shiftName }}</span>
                                                @if ($start)
                                                    <span class="mt-0.5 block whitespace-nowrap text-[11px] font-medium opacity-80">{{ $start }} – {{ $end }}</span>
                                                @endif
                                            </a>
                                        @empty
                                            <span class="flex min-h-[64px] items-center justify-center rounded-xl border border-dashed border-slate-200 text-xs text-slate-300">—</span>
                                        @endforelse
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($days) + 1 }}" class="px-6 py-16 text-center">
                                <p class="text-base font-bold text-slate-800">No active staff found</p>
                                <p class="mt-1 text-sm text-slate-500">Add or activate staff members to build a schedule.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center gap-4 border-t border-slate-100 px-4 py-3 text-xs font-medium text-slate-500">
            <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded bg-blue-200"></span>Scheduled shift</span>
            <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded bg-amber-200"></span>Rest day</span>
            <span class="ml-auto">Select a shift to view or edit its details.</span>
        </div>
    </section>

    <div id="generateModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="generateModalTitle">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-8">
            <div class="mb-5">
                <h3 id="generateModalTitle" class="text-xl font-bold text-slate-900">Generate weekly schedule</h3>
                <p class="mt-1 text-sm text-slate-500">Create a draft from active shift templates and employee availability.</p>
            </div>
            <form method="POST" action="{{ route('schedules.generate') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="generate_week_start" class="mb-1.5 block text-sm font-semibold text-slate-700">Week containing</label>
                    <input type="date" name="week_start_date" id="generate_week_start" required value="{{ old('week_start_date', $weekStart->toDateString()) }}"
                           class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
                    @error('week_start_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-slate-500">The selected date is normalized to the start of its week.</p>
                </div>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" data-close-schedule-modal class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-xl bg-[#E31E24] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-red-700">Generate draft</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('generateModal');
            const openButton = document.querySelector('[data-open-schedule-modal]');
            const closeButton = document.querySelector('[data-close-schedule-modal]');

            if (!modal || !openButton || !closeButton) {
                return;
            }

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            openButton.addEventListener('click', () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                modal.querySelector('input[type="date"]')?.focus();
            });
            closeButton.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
@endsection
