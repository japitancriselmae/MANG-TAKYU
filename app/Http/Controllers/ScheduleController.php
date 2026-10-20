<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        $schedules = Schedule::with('employee')
            ->orderByDesc('schedule_date')
            ->orderBy('start_time')
            ->get();

        return view('schedules.index', compact('schedules'));
    }

    public function create(): View
    {
        $employees = Employee::where('status', 'Active')
            ->orderBy('first_name')
            ->get();

        return view('schedules.create', compact('employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,employee_id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $days = (int) $startDate->diffInDays($endDate) + 1;

        if ($days > 31) {
            return back()
                ->withErrors([
                    'end_date' => 'A schedule range cannot be longer than 31 days.',
                ])
                ->withInput();
        }

        if ($validated['status'] === 'Active') {
            $overlap = Schedule::query()
                ->where('employee_id', $validated['employee_id'])
                ->whereBetween('schedule_date', [$validated['start_date'], $validated['end_date']])
                ->where('status', 'Active')
                ->where('start_time', '<', $validated['end_time'])
                ->where('end_time', '>', $validated['start_time'])
                ->exists();

            if ($overlap) {
                return back()
                    ->withErrors([
                        'schedule' => 'This employee already has an overlapping active schedule on one or more dates in this range.',
                    ])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($validated, $startDate, $days): void {
            for ($dayOffset = 0; $dayOffset < $days; $dayOffset++) {
                Schedule::create([
                    'employee_id' => $validated['employee_id'],
                    'schedule_date' => $startDate->copy()->addDays($dayOffset)->toDateString(),
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'status' => $validated['status'],
                ]);
            }
        });

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Schedule created for '.$days.' '.($days === 1 ? 'day' : 'days').'.');
    }

    public function show(Schedule $schedule): View
    {
        $schedule->load('employee');

        return view('schedules.show', compact('schedule'));
    }

    public function edit(Schedule $schedule): View
    {
        $employees = Employee::where('status', 'Active')
            ->orderBy('first_name')
            ->get();

        return view('schedules.edit', compact('schedule', 'employees'));
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
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

    public function destroy(Schedule $schedule): RedirectResponse
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
