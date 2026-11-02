@extends('layouts.app')

@section('title', 'Shift Template — '.$template->name)
@section('page-title', 'Shift Template Details')
@section('page-subtitle', $template->name.($template->start_time ? ' · '.\Carbon\Carbon::parse($template->start_time)->format('g:i A').' to '.\Carbon\Carbon::parse($template->end_time)->format('g:i A') : ' · Rest day'))

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">{{ $template->name }}</h2>
        <p class="text-sm text-slate-500 mt-0.5">
            @if ($template->start_time && $template->end_time)
                {{ \Carbon\Carbon::parse($template->start_time)->format('g:i A') }} — {{ \Carbon\Carbon::parse($template->end_time)->format('g:i A') }}
            @else
                Rest day
            @endif
            @if ($template->is_overnight)
                <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700">Overnight</span>
            @endif
        </p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('shift-templates.edit', $template) }}"
           class="px-5 py-2.5 rounded-xl bg-blue-100 text-blue-700 font-semibold hover:bg-blue-200 transition">
            Edit
        </a>
        <a href="{{ route('shift-templates.index') }}"
           class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">
            Back to List
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">Start Time</h3>
        <p class="text-2xl font-bold text-slate-800">
            {{ $template->start_time ? \Carbon\Carbon::parse($template->start_time)->format('h:i A') : '—' }}
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">End Time</h3>
        <p class="text-2xl font-bold text-slate-800">
            {{ $template->end_time ? \Carbon\Carbon::parse($template->end_time)->format('h:i A') : '—' }}
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">Required Staff</h3>
        <p class="text-2xl font-bold text-slate-800">
            {{ $template->required_employee_count }} employee{{ $template->required_employee_count != 1 ? 's' : '' }}
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">Overnight Shift</h3>
        <p class="text-slate-700">
            @if ($template->is_overnight)
                <span class="inline-flex items-center gap-1.5 text-emerald-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Yes
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 text-slate-500">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    No
                </span>
            @endif
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">Status</h3>
        <p class="text-slate-700">
            @if ($template->is_active)
                <span class="inline-flex items-center gap-1.5 text-emerald-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Active
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 text-slate-500">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    Inactive
                </span>
            @endif
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 col-span-full">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">Actions</h3>
        <div class="flex gap-3">
            <a href="{{ route('shift-templates.edit', $template) }}"
               class="px-5 py-2.5 rounded-xl bg-blue-100 text-blue-700 font-semibold hover:bg-blue-200 transition">
                Edit Template
            </a>
            <form action="{{ route('shift-templates.destroy', $template) }}" method="POST"
                  onsubmit="return confirm('Delete this shift template?');"
                  class="inline">
                @csrf @method('DELETE')
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-red-100 text-red-700 font-semibold hover:bg-red-200 transition">
                    Delete Template
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
