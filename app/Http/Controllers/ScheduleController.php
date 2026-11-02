<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\ShiftTemplate;
use App\Services\ActivityLogger;
use App\Services\SchedulingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'week_start_date' => ['nullable', 'date_format:Y-m-d'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,employee_id'],
            'shift_template_id' => ['nullable', 'integer', 'exists:shift_templates,shift_template_id'],
        ]);

        $weekStart = isset($validated['week_start_date'])
            ? Carbon::parse($validated['week_start_date'])->startOfWeek()
            : Carbon::now()->startOfWeek();

        $weekEnd = $weekStart->copy()->endOfWeek();

        $employeeOptions = Employee::schedulable()
            ->where(function ($query) use ($weekStart, $weekEnd): void {
                $query->where('status', 'Active')
                    ->orWhereHas('schedules', function ($schedulesQuery) use ($weekStart, $weekEnd): void {
                        $schedulesQuery
                            ->whereDate('schedule_date', '>=', $weekStart->toDateString())
                            ->whereDate('schedule_date', '<=', $weekEnd->toDateString());
                    });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $employeesQuery = Employee::schedulable()
            ->where(function ($query) use ($weekStart, $weekEnd): void {
                $query->where('status', 'Active')
                    ->orWhereHas('schedules', function ($schedulesQuery) use ($weekStart, $weekEnd): void {
                        $schedulesQuery
                            ->whereDate('schedule_date', '>=', $weekStart->toDateString())
                            ->whereDate('schedule_date', '<=', $weekEnd->toDateString());
                    });
            })
            ->with('role')
            ->orderBy('first_name')
            ->orderBy('last_name');

        if (isset($validated['employee_id'])) {
            $employeesQuery->where('employee_id', $validated['employee_id']);
        }

        $employees = $employeesQuery->get();

        $schedulesQuery = Schedule::with(['employee', 'shiftTemplate'])
            ->whereDate('schedule_date', '>=', $weekStart->toDateString())
            ->whereDate('schedule_date', '<=', $weekEnd->toDateString())
            ->whereHas('employee', fn ($query) => $query->schedulable())
            ->orderBy('employee_id')
            ->orderBy('schedule_date')
            ->orderBy('start_time');

        if (isset($validated['employee_id'])) {
            $schedulesQuery->where('employee_id', $validated['employee_id']);
        }

        if (isset($validated['shift_template_id'])) {
            $schedulesQuery->where('shift_template_id', $validated['shift_template_id']);
        }

        $schedules = $schedulesQuery->get();

        $employeeSchedules = [];
        foreach ($employees as $employee) {
            $employeeSchedules[$employee->employee_id] = [];
        }
        foreach ($schedules as $schedule) {
            $dateKey = $schedule->schedule_date->format('Y-m-d');
            $employeeSchedules[$schedule->employee_id][$dateKey][] = $schedule;
        }

        $days = [];
        $current = $weekStart->copy();
        while ($current <= $weekEnd) {
            $days[] = [
                'date' => $current->copy(),
                'key' => $current->format('Y-m-d'),
                'label' => $current->format('M j'),
                'weekday' => $current->format('l'),
            ];
            $current->addDay();
        }

        $weekSchedules = Schedule::query()
            ->whereDate('schedule_date', '>=', $weekStart->toDateString())
            ->whereDate('schedule_date', '<=', $weekEnd->toDateString())
            ->whereHas('employee', fn ($query) => $query->schedulable())
            ->orderBy('schedule_id')
            ->get();

        $weekStatuses = $weekSchedules->pluck('schedule_status')->unique();
        $scheduleStatus = $weekStatuses->count() === 1 ? $weekStatuses->first() : null;
        $scheduleActions = $weekSchedules->isNotEmpty()
            ? $weekSchedules->first()
            : null;
        $hasDraftSchedules = $weekSchedules->contains('schedule_status', Schedule::STATUS_DRAFT);
        $hasApprovedSchedules = $weekSchedules->contains('schedule_status', Schedule::STATUS_APPROVED);

        $weeklyHoursByEmployee = [];
        foreach ($weekSchedules as $weekSchedule) {
            if (
                $weekSchedule->status !== 'Active'
                || ! $weekSchedule->start_time
                || ! $weekSchedule->end_time
            ) {
                continue;
            }

            $startTime = Carbon::parse('2000-01-01 '.$weekSchedule->start_time);
            $endTime = Carbon::parse('2000-01-01 '.$weekSchedule->end_time);
            if ($endTime->lessThanOrEqualTo($startTime)) {
                $endTime->addDay();
            }

            $weeklyHoursByEmployee[$weekSchedule->employee_id] =
                ($weeklyHoursByEmployee[$weekSchedule->employee_id] ?? 0)
                + $startTime->diffInMinutes($endTime) / 60;
        }

        $shiftTemplates = ShiftTemplate::query()->orderBy('name')->get();
        $filters = array_filter([
            'employee_id' => $validated['employee_id'] ?? null,
            'shift_template_id' => $validated['shift_template_id'] ?? null,
        ]);

        return view('schedules.index', compact(
            'employees',
            'employeeSchedules',
            'days',
            'weekStart',
            'weekEnd',
            'scheduleStatus',
            'scheduleActions',
            'hasDraftSchedules',
            'hasApprovedSchedules',
            'shiftTemplates',
            'employeeOptions',
            'validated',
            'weeklyHoursByEmployee',
            'filters',
        ));
    }

    public function generate(
        Request $request,
        SchedulingService $schedulingService,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $this->authorize('manageSchedules');

        $validated = $request->validate([
            'week_start_date' => ['required', 'date_format:Y-m-d'],
        ]);

        $weekStart = Carbon::parse($validated['week_start_date'])->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $manager = $request->user()->employee;

        if (! $manager) {
            $activityLogger->record(
                action: 'schedule.generation_failed',
                module: 'scheduling',
                description: 'Schedule generation failed because the manager has no employee record.',
                status: ActivityLog::STATUS_FAILURE,
            );

            return back()->withErrors([
                'week_start_date' => 'Your account is not linked to an employee record.',
            ]);
        }

        $result = $schedulingService->generateWeeklySchedule(
            $weekStart,
            $weekEnd,
            $manager->employee_id,
            $activityLogger,
        );

        if (! $result['success']) {
            $activityLogger->record(
                action: 'schedule.generation_failed',
                module: 'scheduling',
                description: 'Schedule generation failed: '.$result['message'],
                status: ActivityLog::STATUS_FAILURE,
                newValues: ['week_start_date' => $weekStart->toDateString()],
            );

            return back()->withErrors([
                'week_start_date' => $result['message'],
            ])->withInput();
        }

        $message = $result['message'];
        if (! empty($result['shortages'])) {
            $message .= ' Staffing shortage detected: '.implode(', ', $result['shortages']);
        }

        return redirect()
            ->route('schedules.index', ['week_start_date' => $weekStart->toDateString()])
            ->with('success', $message);
    }

    public function approve(Schedule $schedule, ActivityLogger $activityLogger): RedirectResponse
    {
        $this->authorize('manageSchedules');
        $weekStart = $schedule->schedule_date->copy()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $weekSchedules = DB::transaction(function () use ($weekStart, $weekEnd, $activityLogger): Collection {
            $weekSchedules = Schedule::query()
                ->whereDate('schedule_date', '>=', $weekStart->toDateString())
                ->whereDate('schedule_date', '<=', $weekEnd->toDateString())
                ->whereHas('employee', fn ($query) => $query->schedulable())
                ->where('schedule_status', Schedule::STATUS_DRAFT)
                ->lockForUpdate()
                ->get();

            foreach ($weekSchedules as $weekSchedule) {
                $weekSchedule->update([
                    'schedule_status' => Schedule::STATUS_APPROVED,
                    'approved_by' => auth()->user()->employee_id,
                    'approved_at' => now(),
                ]);
            }

            if ($weekSchedules->isNotEmpty()) {
                $activityLogger->record(
                    action: 'schedule.approved',
                    module: 'scheduling',
                    description: 'Weekly schedule approved.',
                    subject: $weekSchedules->first(),
                    oldValues: ['status' => Schedule::STATUS_DRAFT],
                    newValues: [
                        'status' => Schedule::STATUS_APPROVED,
                        'week_start_date' => $weekStart->toDateString(),
                        'week_end_date' => $weekEnd->toDateString(),
                        'assignment_count' => $weekSchedules->count(),
                    ],
                );
            }

            return $weekSchedules;
        });

        if ($weekSchedules->isEmpty()) {
            return back()->withErrors([
                'schedule' => 'No draft schedule found for this week.',
            ]);
        }

        return redirect()
            ->route('schedules.index', ['week_start_date' => $weekStart->toDateString()])
            ->with('success', 'Weekly schedule approved successfully.');
    }

    public function publish(Schedule $schedule, ActivityLogger $activityLogger): RedirectResponse
    {
        $this->authorize('manageSchedules');
        $weekStart = $schedule->schedule_date->copy()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $weekSchedules = DB::transaction(function () use ($weekStart, $weekEnd, $activityLogger): Collection {
            $weekSchedules = Schedule::query()
                ->whereDate('schedule_date', '>=', $weekStart->toDateString())
                ->whereDate('schedule_date', '<=', $weekEnd->toDateString())
                ->whereHas('employee', fn ($query) => $query->schedulable())
                ->where('schedule_status', Schedule::STATUS_APPROVED)
                ->lockForUpdate()
                ->get();

            foreach ($weekSchedules as $weekSchedule) {
                $weekSchedule->update([
                    'schedule_status' => Schedule::STATUS_PUBLISHED,
                ]);
            }

            if ($weekSchedules->isNotEmpty()) {
                $activityLogger->record(
                    action: 'schedule.published',
                    module: 'scheduling',
                    description: 'Weekly schedule published.',
                    subject: $weekSchedules->first(),
                    oldValues: ['status' => Schedule::STATUS_APPROVED],
                    newValues: [
                        'status' => Schedule::STATUS_PUBLISHED,
                        'week_start_date' => $weekStart->toDateString(),
                        'week_end_date' => $weekEnd->toDateString(),
                        'assignment_count' => $weekSchedules->count(),
                    ],
                );
            }

            return $weekSchedules;
        });

        if ($weekSchedules->isEmpty()) {
            return back()->withErrors([
                'schedule' => 'No approved schedule found for this week.',
            ]);
        }

        return redirect()
            ->route('schedules.index', ['week_start_date' => $weekStart->toDateString()])
            ->with('success', 'Weekly schedule published successfully.');
    }

    public function create(): View
    {
        $employees = Employee::schedulable()
            ->where('status', 'Active')
            ->orderBy('first_name')
            ->get();

        return view('schedules.create', compact('employees'));
    }

    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,employee_id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        if (! Employee::schedulable()->whereKey($validated['employee_id'])->exists()) {
            return back()
                ->withErrors(['employee_id' => 'Only staff members can be scheduled.'])
                ->withInput();
        }

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
                ->whereDate('schedule_date', '>=', $validated['start_date'])
                ->whereDate('schedule_date', '<=', $validated['end_date'])
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

        DB::transaction(function () use ($validated, $startDate, $days, $request, $activityLogger): void {
            $firstSchedule = null;

            for ($dayOffset = 0; $dayOffset < $days; $dayOffset++) {
                $scheduleDate = $startDate->copy()->addDays($dayOffset);

                $schedule = Schedule::create([
                    'employee_id' => $validated['employee_id'],
                    'schedule_date' => $scheduleDate->toDateString(),
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'status' => $validated['status'],
                    'week_start_date' => $scheduleDate->copy()->startOfWeek()->toDateString(),
                    'week_end_date' => $scheduleDate->copy()->endOfWeek()->toDateString(),
                    'schedule_status' => Schedule::STATUS_DRAFT,
                    'created_by' => $request->user()->employee_id,
                ]);

                $firstSchedule ??= $schedule;
            }

            $activityLogger->record(
                action: 'schedule.shift_assigned',
                module: 'scheduling',
                description: 'Employee schedule assigned.',
                subject: $firstSchedule,
                newValues: [
                    'employee_id' => $validated['employee_id'],
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $startDate->copy()->addDays($days - 1)->toDateString(),
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'schedule_count' => $days,
                ],
            );
        });

        return redirect()
            ->route('schedules.index', [
                'week_start_date' => $startDate->copy()->startOfWeek()->toDateString(),
            ])
            ->with('success', 'Schedule created for '.$days.' '.($days === 1 ? 'day' : 'days').'.');
    }

    public function show(Schedule $schedule): View
    {
        $schedule->load(['employee.role', 'shiftTemplate']);

        return view('schedules.show', compact('schedule'));
    }

    public function edit(Schedule $schedule): View
    {
        $employees = Employee::schedulable()
            ->where(function ($query) use ($schedule): void {
                $query->where('status', 'Active')
                    ->orWhere('employee_id', $schedule->employee_id);
            })
            ->orderBy('first_name')
            ->get();

        return view('schedules.edit', compact('schedule', 'employees'));
    }

    public function update(
        Request $request,
        Schedule $schedule,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,employee_id'],
            'schedule_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        if (! Employee::schedulable()->whereKey($validated['employee_id'])->exists()) {
            return back()
                ->withErrors(['employee_id' => 'Only staff members can be scheduled.'])
                ->withInput();
        }

        $overlap = $validated['status'] === 'Active'
            && Schedule::query()
                ->where('employee_id', $validated['employee_id'])
                ->whereDate('schedule_date', $validated['schedule_date'])
                ->where('status', 'Active')
                ->where('schedule_id', '!=', $schedule->schedule_id)
                ->where('start_time', '<', $validated['end_time'])
                ->where('end_time', '>', $validated['start_time'])
                ->exists();

        if ($overlap) {
            return back()
                ->withErrors([
                    'schedule' => 'This employee already has an overlapping active schedule on this date.',
                ])
                ->withInput();
        }

        $scheduleDate = Carbon::parse($validated['schedule_date']);
        DB::transaction(function () use ($validated, $scheduleDate, $schedule, $activityLogger): void {
            $schedule = Schedule::query()
                ->whereKey($schedule->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $oldValues = $this->auditValues($schedule);
            $schedule->update([
                ...$validated,
                'shift_template_id' => $schedule->start_time ? $schedule->shift_template_id : null,
                'week_start_date' => $scheduleDate->copy()->startOfWeek()->toDateString(),
                'week_end_date' => $scheduleDate->copy()->endOfWeek()->toDateString(),
            ]);
            $newValues = $this->auditValues($schedule);
            $shiftChanged = $oldValues['employee_id'] !== $newValues['employee_id']
                || $oldValues['schedule_date'] !== $newValues['schedule_date']
                || $oldValues['start_time'] !== $newValues['start_time']
                || $oldValues['end_time'] !== $newValues['end_time'];

            $activityLogger->record(
                action: $shiftChanged ? 'schedule.shift_changed' : 'schedule.updated',
                module: 'scheduling',
                description: $shiftChanged ? 'Employee shift assignment changed.' : 'Schedule updated.',
                subject: $schedule,
                oldValues: $oldValues,
                newValues: $newValues,
            );
        });

        return redirect()
            ->route('schedules.index', [
                'week_start_date' => $scheduleDate->copy()->startOfWeek()->toDateString(),
            ])
            ->with('success', 'Schedule updated successfully.');
    }

    public function destroy(Schedule $schedule, ActivityLogger $activityLogger): RedirectResponse
    {
        $weekStart = $schedule->schedule_date->copy()->startOfWeek();

        if ($schedule->attendances()->exists()) {
            return back()->withErrors([
                'schedule' => 'This schedule cannot be deleted because it has attendance records.',
            ]);
        }

        DB::transaction(function () use ($schedule, $activityLogger): void {
            $oldValues = $this->auditValues($schedule);
            $schedule->delete();

            $activityLogger->record(
                action: 'schedule.deleted',
                module: 'scheduling',
                description: 'Employee schedule deleted.',
                subject: $schedule,
                oldValues: $oldValues,
            );
        });

        return redirect()
            ->route('schedules.index', ['week_start_date' => $weekStart->toDateString()])
            ->with('success', 'Schedule deleted successfully.');
    }

    /**
     * @return array{ employee_id: int, schedule_date: string, start_time: string|null, end_time: string|null, status: string, schedule_status: string }
     */
    private function auditValues(Schedule $schedule): array
    {
        return [
            'employee_id' => (int) $schedule->employee_id,
            'schedule_date' => $schedule->schedule_date->toDateString(),
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'status' => $schedule->status,
            'schedule_status' => $schedule->schedule_status,
        ];
    }
}
