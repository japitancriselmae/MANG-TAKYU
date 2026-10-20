@extends('layouts.app')

@section('title', 'Attendance')
@section('page-title', 'Attendance')
@section('page-subtitle', 'Track employee clock-in and clock-out records.')

@section('content')

{{-- ============================================================
     DATE FILTER
=========================================================== --}}
<div class="mb-6">
    <form action="{{ route('attendances.index') }}" method="GET"
          class="flex flex-wrap items-end gap-2">
        <div>
            <label for="date"
                   class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 mb-1">
                Attendance Date
            </label>
            <input type="date"
                   id="date"
                   name="date"
                   value="{{ $filterDate?->toDateString() }}"
                   max="{{ now()->toDateString() }}"
                   class="px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-800
                          focus:outline-none focus:ring-2 focus:ring-[#E31E24] focus:border-transparent">
        </div>
        <button type="submit"
                class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm
                       font-semibold transition">
            Filter
        </button>
        @if ($filterDate)
            <a href="{{ route('attendances.index') }}"
               class="px-4 py-2 rounded-lg bg-white ring-1 ring-slate-200 text-slate-600 text-sm
                      font-semibold hover:bg-slate-50 transition">
                Clear
            </a>
        @endif
        @error('date')
            <p class="w-full text-xs font-semibold text-[#E31E24]">{{ $message }}</p>
        @enderror
    </form>

    @if ($filterDate)
        <p class="mt-3 text-sm text-slate-500">
            Showing attendance for
            <span class="font-semibold text-slate-700">{{ $filterDate->format('F j, Y') }}</span>
        </p>
    @endif
</div>

{{-- ============================================================
     ATTENDANCE LOG
============================================================ --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-bold text-slate-800">
            {{ $isManagerLike ? 'Attendance Log' : 'My Attendance Log' }}
        </h3>
        <span class="text-xs text-slate-500">{{ $attendance->count() }} record(s)</span>
    </div>

    @if ($attendance->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">Date</th>
                        @if ($isManagerLike)
                            <th class="px-6 py-3 font-semibold">Employee</th>
                        @endif
                        <th class="px-6 py-3 font-semibold">Time In</th>
                        <th class="px-6 py-3 font-semibold">Time Out</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold">Proof Photo</th>
                        <th class="px-6 py-3 font-semibold text-right">Late / UT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($attendance as $record)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-slate-700 font-medium">
                                {{ $record->date?->format('M d, Y') }}
                            </td>
                            @if ($isManagerLike)
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-800">
                                        {{ $record->employee->first_name }} {{ $record->employee->last_name }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $record->employee->role->role_name ?? '' }}
                                    </div>
                                </td>
                            @endif
                            <td class="px-6 py-4 text-slate-700">
                                {{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $record->time_out ? \Carbon\Carbon::parse($record->time_out)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $badge = match ($record->status) {
                                        'Present' => 'bg-emerald-100 text-emerald-700',
                                        'Late'    => 'bg-amber-100 text-amber-700',
                                        'Absent'  => 'bg-red-100 text-[#E31E24]',
                                        default   => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold {{ $badge }}">
                                    {{ $record->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($record->proof_image)
                                    <button type="button"
                                            onclick="openImageViewer('{{ asset('storage/' . $record->proof_image) }}')"
                                            class="inline-block group cursor-pointer">
                                        <div class="w-14 h-14 rounded-lg overflow-hidden border-2 border-slate-200 group-hover:border-[#E31E24] transition">
                                            <img src="{{ asset('storage/' . $record->proof_image) }}"
                                                 alt="Proof"
                                                 class="w-full h-full object-cover">
                                        </div>
                                        <div class="text-[10px] text-slate-500 text-center mt-1 group-hover:text-[#E31E24]">
                                            Click to view
                                        </div>
                                    </button>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-xs font-semibold">
                                        No photo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="text-xs text-slate-500">
                                    Late: <span class="font-semibold text-slate-700">{{ $record->late_minutes }}m</span>
                                </div>
                                <div class="text-xs text-slate-500">
                                    UT: <span class="font-semibold text-slate-700">{{ $record->undertime_minutes }}m</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-16 text-center">
            <div class="text-4xl mb-3">📋</div>
            <p class="font-semibold text-slate-700">No attendance logs yet</p>
            <p class="text-sm text-slate-500 mt-1">Log entries will appear here after the first clock-in.</p>
        </div>
    @endif
</div>


{{-- ============================================================
     IMAGE VIEWER MODAL (same-tab lightbox)
============================================================ --}}
<div id="imageViewer"
     class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden items-center justify-center p-4"
     onclick="closeImageViewer(event)">

    <div class="relative max-w-3xl w-full" onclick="event.stopPropagation()">

        {{-- Close button --}}
        <button type="button"
                onclick="closeImageViewer(event)"
                class="absolute -top-12 right-0 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20
                       text-white text-xl font-bold flex items-center justify-center transition">
            ✕
        </button>

        {{-- Image --}}
        <div class="bg-white rounded-2xl overflow-hidden shadow-2xl">
            <img id="viewerImage"
                 src=""
                 alt="Preview"
                 class="w-full max-h-[75vh] object-contain bg-slate-900">
        </div>

        {{-- Caption --}}
        <div class="mt-3 text-center text-xs text-white/70">
            Click outside or press ESC to close
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openImageViewer(src) {
        const viewer = document.getElementById('imageViewer');
        const img    = document.getElementById('viewerImage');

        img.src = src;
        viewer.classList.remove('hidden');
        viewer.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeImageViewer(event) {
        if (event && event.target !== event.currentTarget) return;

        const viewer = document.getElementById('imageViewer');
        viewer.classList.add('hidden');
        viewer.classList.remove('flex');
        document.body.style.overflow = '';
        document.getElementById('viewerImage').src = '';
    }

    // Close on ESC key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeImageViewer({ target: document.getElementById('imageViewer'), currentTarget: document.getElementById('imageViewer') });
        }
    });
</script>
@endpush

@endsection