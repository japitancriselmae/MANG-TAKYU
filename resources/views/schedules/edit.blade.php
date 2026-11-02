@extends('layouts.app')

@section('title', 'Edit Schedule')
@section('page-title', 'Edit Schedule')
@section('page-subtitle', 'Update the employee\'s work schedule.')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="mb-5">
        <a href="{{ route('schedules.show', $schedule) }}" class="text-sm font-semibold text-slate-500 transition hover:text-slate-800">
            &larr; Back to schedule details
        </a>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('schedules.update', $schedule->schedule_id) }}" class="space-y-5">
            @csrf @method('PUT')

            @if ($errors->has('schedule'))
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first('schedule') }}
                </div>
            @endif

            <div>
                <label for="employee_id" class="block text-sm font-semibold text-slate-700 mb-2">Employee</label>
                <select name="employee_id" id="employee_id" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->employee_id }}"
                            {{ old('employee_id', $schedule->employee_id) == $employee->employee_id ? 'selected' : '' }}>
                            {{ $employee->first_name }} {{ $employee->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="schedule_date" class="block text-sm font-semibold text-slate-700 mb-2">Date</label>
                <input type="date" name="schedule_date" id="schedule_date" required
                       @php $defaultScheduleDate = \Carbon\Carbon::parse($schedule->schedule_date)->format('Y-m-d'); @endphp
                       value="{{ old('schedule_date', $defaultScheduleDate) }}"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                @error('schedule_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_time" class="block text-sm font-semibold text-slate-700 mb-2">Start Time</label>
                    <input type="time" name="start_time" id="start_time" required
                           @php $defaultStartTime = $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : ''; @endphp
                           value="{{ old('start_time', $defaultStartTime) }}"
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl
                                  focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    @error('start_time')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="end_time" class="block text-sm font-semibold text-slate-700 mb-2">End Time</label>
                    <input type="time" name="end_time" id="end_time" required
                           @php $defaultEndTime = $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : ''; @endphp
                           value="{{ old('end_time', $defaultEndTime) }}"
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl
                                  focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    @error('end_time')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="status" class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                <select name="status" id="status" required
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-white
                               focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    <option value="Active"   {{ old('status', $schedule->status) === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ old('status', $schedule->status) === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="flex gap-3 pt-3">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                               shadow-lg shadow-red-500/20 transition">
                    Update Schedule
                </button>
                <a href="{{ route('schedules.index', ['week_start_date' => $schedule->schedule_date->copy()->startOfWeek()->toDateString()]) }}"
                   class="px-6 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold
                          hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>

@endsection