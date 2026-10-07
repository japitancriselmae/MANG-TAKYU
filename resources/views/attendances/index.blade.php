@extends('layouts.app')

@section('title', 'Attendance')
@section('page-title', 'Attendance')
@section('page-subtitle', 'Track employee clock-in and clock-out records.')

@section('content')

@php
    $user = auth()->user();
    $role = $user->employee?->role?->role_name;
    $requiresImage = in_array($role, ['Cashier', 'Kitchen Staff']);
@endphp


{{-- ============================================================
     STAFF — TODAY'S PANEL
============================================================ --}}
@if (!$isManagerLike)

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

        {{-- Today's Schedule --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-[#0B0B0B] text-[#F5A623] flex items-center justify-center">
                    <span class="w-5 h-5">@include('partials.icon', ['name' => 'calendar'])</span>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Today's Schedule</div>
                    <div class="text-sm font-semibold text-slate-700">{{ now()->format('l, M d') }}</div>
                </div>
            </div>

            @if ($todaySchedule)
                <div class="text-2xl font-bold text-slate-800">
                    {{ \Carbon\Carbon::parse($todaySchedule->start_time)->format('h:i A') }}
                    <span class="text-slate-300 font-normal mx-1">–</span>
                    {{ \Carbon\Carbon::parse($todaySchedule->end_time)->format('h:i A') }}
                </div>
                <div class="text-xs text-emerald-600 font-semibold mt-2 inline-flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Active shift
                </div>
            @else
                <div class="text-2xl font-bold text-slate-400">No shift today</div>
                <div class="text-xs text-slate-400 mt-2">You are off duty</div>
            @endif
        </div>

        {{-- Clock In --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-[#E31E24] text-white flex items-center justify-center">
                    <span class="w-5 h-5">@include('partials.icon', ['name' => 'clock'])</span>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Clock In</div>
                    <div class="text-sm font-semibold text-slate-700">Start of shift</div>
                </div>
            </div>

            @if ($todayAttendance?->time_in)
                <div class="text-2xl font-bold text-slate-800">
                    {{ \Carbon\Carbon::parse($todayAttendance->time_in)->format('h:i A') }}
                </div>
                @if ($todayAttendance->late_minutes > 0)
                    <div class="text-xs font-semibold text-[#E31E24] mt-2">
                        Late by {{ $todayAttendance->late_minutes }} min
                    </div>
                @else
                    <div class="text-xs font-semibold text-emerald-600 mt-2">On time ✓</div>
                @endif
            @else
                <div class="text-2xl font-bold text-slate-300">— : —</div>
                <form action="{{ route('attendances.store') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit"
                            class="w-full bg-[#E31E24] hover:bg-red-700 text-white font-semibold py-2.5 rounded-lg
                                   shadow-lg shadow-red-500/20 transition disabled:opacity-50"
                            {{ !$todaySchedule ? 'disabled' : '' }}>
                        Clock In Now
                    </button>
                </form>
                @if (!$todaySchedule)
                    <p class="text-[10px] text-slate-400 mt-2 text-center">No active schedule for today.</p>
                @endif
            @endif
        </div>

        {{-- Clock Out --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-[#F5A623] text-[#0B0B0B] flex items-center justify-center">
                    <span class="w-5 h-5">@include('partials.icon', ['name' => 'clock'])</span>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Clock Out</div>
                    <div class="text-sm font-semibold text-slate-700">End of shift</div>
                </div>
            </div>

            @if ($todayAttendance?->time_out)
                <div class="text-2xl font-bold text-slate-800">
                    {{ \Carbon\Carbon::parse($todayAttendance->time_out)->format('h:i A') }}
                </div>
                @if ($todayAttendance->undertime_minutes > 0)
                    <div class="text-xs font-semibold text-amber-600 mt-2">
                        Undertime: {{ $todayAttendance->undertime_minutes }} min
                    </div>
                @else
                    <div class="text-xs font-semibold text-emerald-600 mt-2">Complete shift ✓</div>
                @endif
            @elseif ($todayAttendance?->time_in)
                <div class="text-2xl font-bold text-slate-300">— : —</div>
                <form action="{{ route('attendances.clock-out', $todayAttendance) }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit"
                            class="w-full bg-[#F5A623] hover:bg-amber-500 text-[#0B0B0B] font-semibold py-2.5 rounded-lg
                                   shadow-lg shadow-amber-500/20 transition">
                        Clock Out Now
                    </button>
                </form>
            @else
                <div class="text-2xl font-bold text-slate-300">— : —</div>
                <p class="text-xs text-slate-400 mt-3">Clock in first to enable clock-out.</p>
            @endif
        </div>
    </div>

    {{-- PROOF PHOTO PANEL --}}
    @if ($requiresImage && $todayAttendance?->time_in)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 mb-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-[#E31E24] text-white flex items-center justify-center font-bold shrink-0">
                    📷
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-slate-800 text-lg">Proof of Attendance</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Upload a photo of yourself at your station. The Manager will see this in their dashboard.
                    </p>
                </div>
            </div>

            @if ($todayAttendance->proof_image)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="md:col-span-1">
                        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-2">Current Photo</div>
                        <button type="button"
                                onclick="openImageViewer('{{ asset('storage/' . $todayAttendance->proof_image) }}')"
                                class="block w-full rounded-xl overflow-hidden border-2 border-emerald-200 hover:border-[#E31E24] transition cursor-pointer">
                            <img src="{{ asset('storage/' . $todayAttendance->proof_image) }}"
                                 alt="Proof"
                                 class="w-full h-40 object-cover">
                        </button>
                        <div class="text-xs text-emerald-600 font-semibold mt-2">
                            ✓ Uploaded successfully
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-2">
                            Replace Photo (optional)
                        </div>
                        <form action="{{ route('attendances.upload-proof', $todayAttendance) }}"
                              method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <input type="file" name="proof_image" accept="image/*" required
                                   class="w-full text-sm px-4 py-3 border border-slate-200 rounded-xl
                                          file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                          file:bg-slate-100 file:text-slate-700 file:text-xs file:font-semibold">
                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold text-sm transition">
                                Replace Photo
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="p-5 rounded-xl bg-amber-50 border border-amber-200 mb-4">
                    <p class="text-sm text-amber-800">
                        <strong>⚠ You haven't uploaded your proof photo yet.</strong>
                        Please upload a picture of yourself at your station.
                    </p>
                </div>

                <form action="{{ route('attendances.upload-proof', $todayAttendance) }}"
                      method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="proof_image" accept="image/*" required
                           class="w-full text-sm px-4 py-3 border border-slate-200 rounded-xl
                                  file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                                  file:bg-[#E31E24] file:text-white file:text-sm file:font-semibold">
                    <button type="submit"
                            class="w-full py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-bold
                                   shadow-lg shadow-red-500/20 transition">
                        Upload Proof Photo
                    </button>
                </form>
            @endif
        </div>
    @endif

@endif


{{-- ============================================================
     ATTENDANCE HISTORY
============================================================ --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-bold text-slate-800">
            {{ $isManagerLike ? 'All Attendance Records' : 'My Attendance History' }}
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
            <p class="font-semibold text-slate-700">No attendance records yet</p>
            <p class="text-sm text-slate-500 mt-1">Records will appear here after the first clock-in.</p>
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