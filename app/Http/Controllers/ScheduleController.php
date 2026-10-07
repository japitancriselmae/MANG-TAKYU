<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::with('employee')
            ->orderByDesc('schedule_date')
            ->orderBy('start_time')
            ->get();

        return view('schedules.index', compact('schedules'));
    }

    public function create()
    {
        $employees = Employee::where('status', 'Active')
            ->orderBy('first_name')
            ->get();

        return view('schedules.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,employee_id'],
            'schedule_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $overlap = Schedule::where('employee_id', $validated['employee_id'])
            ->whereDate('schedule_date', $validated['schedule_date'])
            ->where('status', 'Active')
            ->where(function ($query) use ($validated) {
                $query->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })
            ->exists();

        if ($overlap) {
            return back()
                ->withErrors([
                    'schedule' => 'This employee already has an overlapping active schedule on this date.',
                ])
                ->withInput();
        }

        Schedule::create($validated);

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Schedule created successfully.');
    }

    public function show(Schedule $schedule)
    {
        $schedule->load('employee');

        return view('schedules.show', compact('schedule'));
    }

    public function edit(Schedule $schedule)
    {
        $employees = Employee::where('status', 'Active')
            ->orderBy('first_name')
            ->get();

        return view('schedules.edit', compact('schedule', 'employees'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,employee_id'],
            'schedule_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $overlap = Schedule::where('employee_id', $validated['employee_id'])
            ->whereDate('schedule_date', $validated['schedule_date'])
            ->where('status', 'Active')
            ->where('schedule_id', '!=', $schedule->schedule_id)
            ->where(function ($query) use ($validated) {
                $query->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })
            ->exists();

        if ($overlap) {
            return back()
                ->withErrors([
                    'schedule' => 'This employee already has an overlapping active schedule on this date.',
                ])
                ->withInput();
        }

        $schedule->update($validated);

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Schedule updated successfully.');
    }

    public function destroy(Schedule $schedule)
    {
        if ($schedule->attendances()->exists()) {
            return back()->withErrors([
                'schedule' => 'This schedule cannot be deleted because it has attendance records.',
            ]);
        }

        $schedule->delete();

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Schedule deleted successfully.');
    }
}
