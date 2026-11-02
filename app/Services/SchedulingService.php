<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Schedule;
use App\Models\ShiftTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SchedulingService
{
    const STATUS_DRAFT = 'draft';

    const STATUS_APPROVED = 'approved';

    const STATUS_PUBLISHED = 'published';

    const SHIFT_MORNING = 'Morning';

    const SHIFT_AFTERNOON = 'Afternoon';

    const SHIFT_NIGHT = 'Night';

    const SHIFT_REST = 'Rest Day';

    /**
     * Generate a weekly schedule draft using rotation algorithm.
     */
    public function generateWeeklySchedule(
        Carbon $weekStart,
        Carbon $weekEnd,
        int $createdBy,
        ActivityLogger $activityLogger,
    ): array {
        $employees = Employee::schedulable()
            ->where('status', 'Active')
            ->with('role')
            ->orderBy('employee_id')
            ->get();

        if ($employees->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No active employees found.',
            ];
        }

        $shiftTemplates = ShiftTemplate::active()
            ->orderBy('start_time')
            ->orderBy('shift_template_id')
            ->get();

        if ($shiftTemplates->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No active shift templates found. Please create shift templates first.',
            ];
        }

        $existing = Schedule::query()
            ->whereDate('schedule_date', '>=', $weekStart->toDateString())
            ->whereDate('schedule_date', '<=', $weekEnd->toDateString())
            ->exists();

        if ($existing) {
            return [
                'success' => false,
                'message' => 'Schedules already exist for this week. Edit or remove them before generating a new draft.',
            ];
        }

        // Keep leave exclusions scoped to the dates they cover.
        $approvedLeaves = Leave::approved()
            ->whereDate('start_date', '<=', $weekEnd->toDateString())
            ->whereDate('end_date', '>=', $weekStart->toDateString())
            ->get();

        $employeesOnLeaveByDate = [];
        foreach ($approvedLeaves as $leave) {
            $leaveDate = Carbon::parse(max($leave->start_date->toDateString(), $weekStart->toDateString()));
            $lastLeaveDate = Carbon::parse(min($leave->end_date->toDateString(), $weekEnd->toDateString()));

            while ($leaveDate <= $lastLeaveDate) {
                $employeesOnLeaveByDate[$leaveDate->toDateString()][$leave->employee_id] = true;
                $leaveDate->addDay();
            }
        }

        $restTemplate = $shiftTemplates->firstWhere('name', self::SHIFT_REST);
        $restTemplateId = $restTemplate?->shift_template_id;

        // Get last week's assignments for rotation consideration
        $lastWeekStart = $weekStart->copy()->subWeek();
        $lastWeekEnd = $weekEnd->copy()->subWeek();

        $lastWeekSchedules = Schedule::where('week_start_date', $lastWeekStart->toDateString())
            ->where('week_end_date', $lastWeekEnd->toDateString())
            ->whereNotNull('start_time')
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->get();

        $lastWeekShifts = [];
        foreach ($lastWeekSchedules as $s) {
            $lastWeekShifts[$s->employee_id] = $s->shift_template_id;
        }

        $restPeriodAssignments = Schedule::query()
            ->whereDate('schedule_date', $weekStart->copy()->subDay()->toDateString())
            ->get(['employee_id', 'schedule_date', 'start_time', 'end_time'])
            ->toArray();

        // Track shift counts per employee for fair rotation
        $shiftCounts = [];
        foreach ($employees as $employee) {
            $shiftCounts[$employee->employee_id] = [
                'morning' => 0,
                'afternoon' => 0,
                'night' => 0,
                'rest' => 0,
            ];

            // Count from last week
            if (isset($lastWeekShifts[$employee->employee_id])) {
                $lastTemplate = $shiftTemplates->where('shift_template_id', $lastWeekShifts[$employee->employee_id])->first();
                if ($lastTemplate) {
                    $name = strtolower($lastTemplate->name);
                    if (str_contains($name, 'morning')) {
                        $shiftCounts[$employee->employee_id]['morning']++;
                    } elseif (str_contains($name, 'afternoon')) {
                        $shiftCounts[$employee->employee_id]['afternoon']++;
                    } elseif (str_contains($name, 'night')) {
                        $shiftCounts[$employee->employee_id]['night']++;
                    }
                }
            }
        }

        // Generate assignments for each day
        $assignments = [];
        $current = $weekStart->copy();

        while ($current <= $weekEnd) {
            $dayKey = $current->toDateString();

            // Sort employees by shift count fairness (least recent shifts first)
            $sortedEmployees = $employees->sortBy(function ($employee) use ($shiftCounts) {
                $counts = $shiftCounts[$employee->employee_id];

                return $counts['morning'] + $counts['afternoon'] + $counts['night'] + ($counts['rest'] * 0.5);
            });

            $assignedEmployees = [];

            // Assign required employees for each active shift
            foreach ($shiftTemplates as $template) {
                if (! $template->is_active) {
                    continue;
                }

                $needed = $template->required_employee_count;
                $assigned = 0;

                foreach ($sortedEmployees as $employee) {
                    if ($assigned >= $needed) {
                        break;
                    }

                    if (isset($assignedEmployees[$employee->employee_id])) {
                        continue;
                    }

                    // Skip employees on approved leave
                    if (isset($employeesOnLeaveByDate[$dayKey][$employee->employee_id])) {
                        continue;
                    }

                    // Check rest period: at least 10 hours between shifts
                    if (! $this->checkRestPeriod($employee->employee_id, $current, $template, $restPeriodAssignments)) {
                        continue;
                    }

                    // Avoid assigning same undesirable shift repeatedly (night shifts)
                    if ($this->isUndesirableShiftRepeat($employee->employee_id, $template, $shiftCounts)) {
                        continue;
                    }

                    $assignment = [
                        'employee_id' => $employee->employee_id,
                        'shift_template_id' => $template->shift_template_id,
                        'schedule_date' => $dayKey,
                        'start_time' => $template->start_time,
                        'end_time' => $template->end_time,
                        'status' => 'Active',
                        'week_start_date' => $weekStart->toDateString(),
                        'week_end_date' => $weekEnd->toDateString(),
                        'schedule_status' => self::STATUS_DRAFT,
                        'created_by' => $createdBy,
                    ];
                    $assignments[] = $assignment;
                    $restPeriodAssignments[] = $assignment;

                    $assignedEmployees[$employee->employee_id] = true;
                    $assigned++;

                    // Update shift counts
                    $name = strtolower($template->name);
                    if (str_contains($name, 'morning')) {
                        $shiftCounts[$employee->employee_id]['morning']++;
                    } elseif (str_contains($name, 'afternoon')) {
                        $shiftCounts[$employee->employee_id]['afternoon']++;
                    } elseif (str_contains($name, 'night')) {
                        $shiftCounts[$employee->employee_id]['night']++;
                    }
                }
            }

            // Assign rest days to unassigned employees (not on leave)
            foreach ($sortedEmployees as $employee) {
                if (
                    ! isset($assignedEmployees[$employee->employee_id])
                    && ! isset($employeesOnLeaveByDate[$dayKey][$employee->employee_id])
                ) {
                    $assignments[] = [
                        'employee_id' => $employee->employee_id,
                        'shift_template_id' => $restTemplateId,
                        'schedule_date' => $dayKey,
                        'start_time' => null,
                        'end_time' => null,
                        'status' => 'Active',
                        'week_start_date' => $weekStart->toDateString(),
                        'week_end_date' => $weekEnd->toDateString(),
                        'schedule_status' => self::STATUS_DRAFT,
                        'created_by' => $createdBy,
                    ];

                    $shiftCounts[$employee->employee_id]['rest']++;
                }
            }

            $current->addDay();
        }

        if ($assignments === []) {
            return [
                'success' => false,
                'message' => 'No schedule assignments could be created for this week. Check employee availability and shift requirements.',
            ];
        }

        // Check for staffing shortages
        $shortages = $this->checkStaffingShortages($assignments, $shiftTemplates, $weekStart, $weekEnd);

        // Save to database
        DB::transaction(function () use ($assignments, $weekStart, $weekEnd, $activityLogger): void {
            $firstSchedule = null;

            foreach ($assignments as $assignment) {
                $schedule = Schedule::create($assignment);
                $firstSchedule ??= $schedule;
            }

            $activityLogger->record(
                action: 'schedule.generated',
                module: 'scheduling',
                description: 'Weekly schedule generated as a draft.',
                subject: $firstSchedule,
                newValues: [
                    'week_start_date' => $weekStart->toDateString(),
                    'week_end_date' => $weekEnd->toDateString(),
                    'assignment_count' => count($assignments),
                    'status' => Schedule::STATUS_DRAFT,
                ],
            );
        });

        $message = 'Weekly schedule generated as draft.';
        if (! empty($shortages)) {
            $message .= ' Staffing shortage detected: '.implode(', ', $shortages);
        }

        return [
            'success' => true,
            'message' => $message,
            'shortages' => $shortages,
            'assignments' => $assignments,
        ];
    }

    /**
     * Check if employee has at least 10 hours rest between shifts.
     */
    private function checkRestPeriod(
        int $employeeId,
        Carbon $date,
        $template,
        array $existingAssignments
    ): bool {
        if (! $template->start_time) {
            return true;
        }

        $templateStart = Carbon::parse($date->format('Y-m-d').' '.$template->start_time);

        // Check previous day's assignments
        $prevDay = $date->copy()->subDay();
        foreach ($existingAssignments as $a) {
            if ((int) $a['employee_id'] !== $employeeId) {
                continue;
            }

            if (
                $a['schedule_date'] === $prevDay->toDateString()
                && $a['start_time']
                && $a['end_time']
            ) {
                $prevStart = Carbon::parse($prevDay->toDateString().' '.$a['start_time']);
                $prevEnd = Carbon::parse($prevDay->format('Y-m-d').' '.$a['end_time']);
                if ($prevEnd->lessThanOrEqualTo($prevStart)) {
                    $prevEnd->addDay();
                }

                if ($templateStart->getTimestamp() - $prevEnd->getTimestamp() < 10 * 60 * 60) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if assigning this shift would be an undesirable repeat.
     */
    private function isUndesirableShiftRepeat(
        int $employeeId,
        $template,
        array $shiftCounts
    ): bool {
        if (! isset($shiftCounts[$employeeId])) {
            return false;
        }

        $counts = $shiftCounts[$employeeId];
        $name = strtolower($template->name);

        // Night shifts are undesirable - avoid repeating more than 2 times
        if (str_contains($name, 'night') && $counts['night'] >= 2) {
            return true;
        }

        return false;
    }

    /**
     * Check for staffing shortages across the week.
     */
    public function checkStaffingShortages(
        array $assignments,
        $shiftTemplates,
        Carbon $weekStart,
        Carbon $weekEnd
    ): array {
        $shortages = [];

        $current = $weekStart->copy();
        while ($current <= $weekEnd) {
            $dayKey = $current->toDateString();

            foreach ($shiftTemplates as $template) {
                if (! $template->is_active) {
                    continue;
                }

                $assignedCount = count(array_filter($assignments, function ($a) use ($dayKey, $template) {
                    return $a['schedule_date'] === $dayKey
                        && $a['shift_template_id'] === $template->shift_template_id;
                }));

                if ($assignedCount < $template->required_employee_count) {
                    $shortage = $template->required_employee_count - $assignedCount;
                    $shortages[] = sprintf(
                        '%s (%s): short %d employee(s)',
                        $template->name,
                        $current->format('M d'),
                        $shortage
                    );
                }
            }

            $current->addDay();
        }

        return $shortages;
    }
}
