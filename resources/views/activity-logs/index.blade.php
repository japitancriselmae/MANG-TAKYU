@extends('layouts.app')

@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')
@section('page-subtitle', 'Review important actions across your management system.')

@section('content')

@php
    $cards = [
        [
            'label' => 'Activities Today',
            'value' => $activityStats['today'],
            'description' => 'Recorded since midnight',
            'icon' => 'clock',
            'color' => 'amber',
        ],
        [
            'label' => 'Activities This Week',
            'value' => $activityStats['thisWeek'],
            'description' => 'Since Monday',
            'icon' => 'calendar',
            'color' => 'red',
        ],
        [
            'label' => 'Successful Activities',
            'value' => $activityStats['successful'],
            'description' => 'All recorded activity',
            'icon' => 'book',
            'color' => 'emerald',
        ],
        [
            'label' => 'Failed Activities',
            'value' => $activityStats['failed'],
            'description' => 'All recorded activity',
            'icon' => 'bell',
            'color' => 'rose',
        ],
    ];

    $iconColors = [
        'amber' => 'bg-amber-50 text-amber-600',
        'red' => 'bg-red-50 text-[#E31E24]',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'rose' => 'bg-rose-50 text-rose-600',
    ];
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Activity overview</h2>
            <p class="mt-1 text-sm text-slate-500">A recent record of changes and important system actions.</p>
        </div>
        <a href="{{ route('activity-logs.export', $filters) }}"
           class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
            <span class="h-4 w-4" aria-hidden="true">@include('partials.icon', ['name' => 'receipt'])</span>
            Export filtered CSV
        </a>
    </div>

    <section aria-label="Activity metrics" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">
                            {{ number_format($card['value']) }}
                        </p>
                        <p class="mt-2 text-xs text-slate-400">{{ $card['description'] }}</p>
                    </div>
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $iconColors[$card['color']] }}" aria-hidden="true">
                        <span class="h-5 w-5">
                            @include('partials.icon', ['name' => $card['icon']])
                        </span>
                    </span>
                </div>
            </article>
        @endforeach
    </section>

    <section class="grid grid-cols-1 gap-4 xl:grid-cols-3" aria-label="Activity summaries">
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Daily activity</h2>
                <p class="mt-1 text-xs text-slate-500">Activity recorded per day over the last 7 days.</p>
            </div>
            <ul class="divide-y divide-slate-100 px-5">
                @foreach ($dailyActivitySummaries as $summary)
                    <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <span class="text-slate-600">{{ $summary['label'] }}</span>
                        <span class="font-semibold tabular-nums text-slate-900">{{ number_format($summary['total']) }}</span>
                    </li>
                @endforeach
            </ul>
        </article>

        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Weekly activity</h2>
                <p class="mt-1 text-xs text-slate-500">Current week and the previous 3 weeks.</p>
            </div>
            <ul class="divide-y divide-slate-100 px-5">
                @foreach ($weeklyActivitySummaries as $summary)
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <span class="text-slate-600">{{ $summary['label'] }}</span>
                        <span class="font-semibold tabular-nums text-slate-900">{{ number_format($summary['total']) }}</span>
                    </li>
                @endforeach
            </ul>
        </article>

        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Frequently performed actions</h2>
                <p class="mt-1 text-xs text-slate-500">Top actions recorded in the last 30 days.</p>
            </div>
            @if ($frequentActions->isEmpty())
                <p class="px-5 py-6 text-sm text-slate-500">No actions recorded in the last 30 days.</p>
            @else
                <ul class="divide-y divide-slate-100 px-5">
                    @foreach ($frequentActions as $frequentAction)
                        <li class="flex items-center justify-between gap-3 py-3 text-sm">
                            <span class="min-w-0 truncate font-mono text-xs text-slate-700">{{ $frequentAction->action }}</span>
                            <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold tabular-nums text-amber-700">
                                {{ number_format($frequentAction->total) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

    <form method="GET" action="{{ route('activity-logs.index') }}" class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-4" aria-label="Activity log filters">
        <label class="text-sm font-semibold text-slate-700">
            Search user
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Search actor name"
                   class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal focus:border-[#E31E24] focus:ring-[#E31E24]/20">
        </label>
        <label class="text-sm font-semibold text-slate-700">
            Module
            <select name="module" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-normal">
                <option value="">All modules</option>
                @foreach ($modules as $module)
                    <option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>
                        {{ \Illuminate\Support\Str::headline($module) }}
                    </option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">
            Activity type
            <select name="action" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-normal">
                <option value="">All activity types</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">
            Status
            <select name="status" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-normal">
                <option value="">All statuses</option>
                <option value="success" @selected(($filters['status'] ?? '') === 'success')>Success</option>
                <option value="failure" @selected(($filters['status'] ?? '') === 'failure')>Failure</option>
            </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">
            From
            <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" max="{{ now()->toDateString() }}"
                   class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal">
        </label>
        <label class="text-sm font-semibold text-slate-700">
            To
            <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" max="{{ now()->toDateString() }}"
                   class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal">
        </label>
        <label class="text-sm font-semibold text-slate-700">
            Sort
            <select name="sort" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-normal">
                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest first</option>
                <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest first</option>
            </select>
        </label>
        <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-4">
            <button type="submit" class="rounded-xl bg-[#E31E24] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                Apply filters
            </button>
            <a href="{{ route('activity-logs.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Clear
            </a>
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="recent-activities-heading">
        <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:px-6">
            <h2 id="recent-activities-heading" class="text-base font-bold text-slate-900">Recent system activities</h2>
            <p class="text-sm text-slate-500">{{ number_format($activities->total()) }} matching {{ \Illuminate\Support\Str::plural('activity', $activities->total()) }}</p>
        </div>

        @if ($activities->isEmpty())
            <div class="flex flex-col items-center px-6 py-14 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500" aria-hidden="true">
                    <span class="h-6 w-6">@include('partials.icon', ['name' => 'book'])</span>
                </span>
                <h3 class="mt-4 text-sm font-semibold text-slate-800">No activity recorded yet</h3>
                <p class="mt-1 max-w-sm text-sm text-slate-500">Important actions will appear here as they are recorded.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:px-6">User</th>
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Module</th>
                            <th scope="col" class="min-w-64 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Description</th>
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Date and time</th>
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:px-6">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($activities as $activity)
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-800 sm:px-6">
                                    {{ $activity->actor_name }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 font-mono text-xs font-medium text-slate-700">
                                    {{ $activity->action }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                    {{ \Illuminate\Support\Str::headline($activity->module) }}
                                </td>
                                <td class="max-w-md px-5 py-4 text-sm text-slate-600">
                                    <span class="line-clamp-2">{{ $activity->description }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm tabular-nums text-slate-600">
                                    <time datetime="{{ $activity->created_at->toIso8601String() }}">
                                        {{ $activity->created_at->format('M j, Y \a\t g:i A') }}
                                    </time>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                    @if ($activity->status === \App\Models\ActivityLog::STATUS_SUCCESS)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                            Success
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500" aria-hidden="true"></span>
                                            Failure
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                    <a href="{{ route('activity-logs.show', array_merge(request()->query(), ['activityLog' => $activity])) }}" class="font-semibold text-[#E31E24] hover:underline">
                                        View details
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($activities->hasPages())
                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">{{ $activities->links() }}</div>
            @endif
        @endif
    </section>
</div>
@endsection
