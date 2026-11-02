@extends('layouts.app')

@section('title', 'Shift Templates')
@section('page-title', 'Shift Templates')
@section('page-subtitle', 'Configure employee shift templates.')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Shift Templates</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $templates->count() }} template(s)</p>
    </div>
    <a href="{{ route('shift-templates.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
              text-white font-semibold shadow-lg shadow-red-500/20 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add Template
    </a>
</div>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    @if ($templates->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">Name</th>
                        <th class="px-6 py-3 font-semibold">Start Time</th>
                        <th class="px-6 py-3 font-semibold">End Time</th>
                        <th class="px-6 py-3 font-semibold">Required Staff</th>
                        <th class="px-6 py-3 font-semibold">Overnight</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($templates as $template)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                {{ $template->name }}
                            </td>
                            @php
                                $startTime = $template->start_time ? \Carbon\Carbon::parse($template->start_time)->format('h:i A') : '—';
                                $endTime = $template->end_time ? \Carbon\Carbon::parse($template->end_time)->format('h:i A') : '—';
                            @endphp
                            <td class="px-6 py-4 text-slate-700">
                                {{ $startTime }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $endTime }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $template->required_employee_count }}
                            </td>
                            <td class="px-6 py-4">
                                @if ($template->is_overnight)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        No
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($template->is_active)
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
                                    <a href="{{ route('shift-templates.edit', $template) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                        Edit
                                    </a>
                                    <form action="{{ route('shift-templates.destroy', $template) }}" method="POST"
                                          onsubmit="return confirm('Delete this shift template?');">
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
            <div class="text-5xl mb-3">⏰</div>
            <p class="font-bold text-slate-700">No shift templates yet</p>
            <p class="text-sm text-slate-500 mt-1 mb-6">Create shift templates before generating automatic schedules.</p>
            <a href="{{ route('shift-templates.create') }}"
               class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold transition">
                Create First Template
            </a>
        </div>
    @endif
</div>

@endsection
