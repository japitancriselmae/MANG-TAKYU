@extends('layouts.app')

@section('title', 'Activity Details')
@section('page-title', 'Activity Details')
@section('page-subtitle', 'Read-only audit record')

@section('content')
<div class="mx-auto flex max-w-5xl flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Activity record #{{ $activity->id }}</h2>
            <p class="mt-1 text-sm text-slate-500">This audit record is read-only and cannot be changed.</p>
        </div>
        <a href="{{ route('activity-logs.index', request()->query()) }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <span aria-hidden="true">&larr;</span>
            Back to activity logs
        </a>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="activity-summary-heading">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-7">
            <div class="min-w-0">
                <h3 id="activity-summary-heading" class="font-mono text-sm font-semibold text-slate-700">{{ $activity->action }}</h3>
                <p class="mt-2 text-base font-semibold text-slate-900">{{ $activity->description }}</p>
            </div>
            @if ($activity->status === \App\Models\ActivityLog::STATUS_SUCCESS)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                    Success
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500" aria-hidden="true"></span>
                    Failure
                </span>
            @endif
        </div>

        <dl class="grid grid-cols-1 gap-x-8 gap-y-5 px-5 py-6 sm:grid-cols-2 sm:px-7">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">User</dt>
                <dd class="mt-1.5 text-sm font-medium text-slate-900">{{ $activity->actor_name }}</dd>
                @if ($activity->user_id === null)
                    <p class="mt-1 text-xs text-slate-500">The associated account is no longer available; the audit snapshot is preserved.</p>
                @endif
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Module</dt>
                <dd class="mt-1.5 text-sm font-medium text-slate-900">{{ \Illuminate\Support\Str::headline($activity->module) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date and time</dt>
                <dd class="mt-1.5 text-sm font-medium tabular-nums text-slate-900">
                    <time datetime="{{ $activity->created_at->toIso8601String() }}">
                        {{ $activity->created_at->format('l, F j, Y \a\t g:i:s A') }}
                    </time>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Related record</dt>
                <dd class="mt-1.5 text-sm font-medium text-slate-900">
                    @if ($subjectType !== null && $activity->subject_id !== null)
                        {{ $subjectType }} #{{ $activity->subject_id }}
                    @else
                        Not linked to a record
                    @endif
                </dd>
            </div>
        </dl>
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2" aria-label="Activity value comparison">
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Previous values</h3>
            </div>
            @if ($activity->old_values !== null && $activity->old_values !== [])
                <pre class="max-h-96 overflow-auto whitespace-pre-wrap break-words bg-slate-50 px-5 py-4 text-xs leading-6 text-slate-700">{{ json_encode($activity->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
            @else
                <p class="px-5 py-6 text-sm text-slate-500">No previous values were captured.</p>
            @endif
        </article>

        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">New values</h3>
            </div>
            @if ($activity->new_values !== null && $activity->new_values !== [])
                <pre class="max-h-96 overflow-auto whitespace-pre-wrap break-words bg-slate-50 px-5 py-4 text-xs leading-6 text-slate-700">{{ json_encode($activity->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
            @else
                <p class="px-5 py-6 text-sm text-slate-500">No new values were captured.</p>
            @endif
        </article>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="request-information-heading">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 id="request-information-heading" class="text-sm font-bold text-slate-900">Request information</h3>
            <p class="mt-1 text-xs text-slate-500">Captured metadata for troubleshooting and audit context.</p>
        </div>
        <dl class="grid grid-cols-1 gap-x-8 gap-y-5 px-5 py-5 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">IP address</dt>
                <dd class="mt-1.5 break-all font-mono text-sm text-slate-800">{{ $activity->ip_address ?? 'Not captured' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">User agent</dt>
                <dd class="mt-1.5 break-words text-sm text-slate-800">{{ $activity->user_agent ?? 'Not captured' }}</dd>
            </div>
        </dl>
    </section>
</div>
@endsection
