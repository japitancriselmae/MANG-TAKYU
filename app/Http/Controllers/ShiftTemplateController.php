<?php

namespace App\Http\Controllers;

use App\Models\ShiftTemplate;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShiftTemplateController extends Controller
{
    public function index(): View
    {
        $templates = ShiftTemplate::orderBy('name')->get();

        return view('shift-templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('shift-templates.create');
    }

    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $isRestDay = $this->isRestDay($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_time' => [$isRestDay ? 'nullable' : 'required', 'date_format:H:i'],
            'end_time' => [$isRestDay ? 'nullable' : 'required', 'date_format:H:i'],
            'is_overnight' => ['required', 'boolean'],
            'required_employee_count' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $this->validateShiftTimes($request);

        DB::transaction(function () use ($isRestDay, $validated, $request, $activityLogger): void {
            $template = ShiftTemplate::create([
                'name' => $isRestDay ? 'Rest Day' : trim($validated['name']),
                'start_time' => $isRestDay ? null : $validated['start_time'],
                'end_time' => $isRestDay ? null : $validated['end_time'],
                'is_overnight' => ! $isRestDay && $request->boolean('is_overnight'),
                'required_employee_count' => $isRestDay ? 0 : $validated['required_employee_count'],
                'is_active' => $request->boolean('is_active'),
            ]);

            $activityLogger->record(
                action: 'schedule.shift_template_created',
                module: 'scheduling',
                description: 'Shift template created.',
                subject: $template,
                newValues: $this->auditValues($template),
            );
        });

        return redirect()
            ->route('shift-templates.index')
            ->with('success', 'Shift template created successfully.');
    }

    public function show(ShiftTemplate $template): View
    {
        return view('shift-templates.show', compact('template'));
    }

    public function edit(ShiftTemplate $template): View
    {
        return view('shift-templates.edit', compact('template'));
    }

    public function update(
        Request $request,
        ShiftTemplate $template,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $isRestDay = $this->isRestDay($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_time' => [$isRestDay ? 'nullable' : 'required', 'date_format:H:i'],
            'end_time' => [$isRestDay ? 'nullable' : 'required', 'date_format:H:i'],
            'is_overnight' => ['required', 'boolean'],
            'required_employee_count' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $this->validateShiftTimes($request);

        DB::transaction(function () use ($isRestDay, $validated, $request, $template, $activityLogger): void {
            $template = ShiftTemplate::query()
                ->whereKey($template->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $oldValues = $this->auditValues($template);
            $template->update([
                'name' => $isRestDay ? 'Rest Day' : trim($validated['name']),
                'start_time' => $isRestDay ? null : $validated['start_time'],
                'end_time' => $isRestDay ? null : $validated['end_time'],
                'is_overnight' => ! $isRestDay && $request->boolean('is_overnight'),
                'required_employee_count' => $isRestDay ? 0 : $validated['required_employee_count'],
                'is_active' => $request->boolean('is_active'),
            ]);

            $activityLogger->record(
                action: 'schedule.shift_template_updated',
                module: 'scheduling',
                description: 'Shift template updated.',
                subject: $template,
                oldValues: $oldValues,
                newValues: $this->auditValues($template),
            );
        });

        return redirect()
            ->route('shift-templates.index')
            ->with('success', 'Shift template updated successfully.');
    }

    public function destroy(ShiftTemplate $template, ActivityLogger $activityLogger): RedirectResponse
    {
        DB::transaction(function () use ($template, $activityLogger): void {
            $oldValues = $this->auditValues($template);
            $template->delete();

            $activityLogger->record(
                action: 'schedule.shift_template_deleted',
                module: 'scheduling',
                description: 'Shift template deleted.',
                subject: $template,
                oldValues: $oldValues,
            );
        });

        return redirect()
            ->route('shift-templates.index')
            ->with('success', 'Shift template deleted successfully.');
    }

    private function validateShiftTimes(Request $request): void
    {
        if ($this->isRestDay($request)) {
            return;
        }

        $startTime = $request->string('start_time')->toString();
        $endTime = $request->string('end_time')->toString();
        $timesAreOvernight = $endTime < $startTime;

        if ($startTime === $endTime || $timesAreOvernight !== $request->boolean('is_overnight')) {
            throw ValidationException::withMessages([
                'end_time' => $request->boolean('is_overnight')
                    ? 'An overnight shift must end before it starts.'
                    : 'The end time must be after the start time unless the shift is overnight.',
            ]);
        }
    }

    private function isRestDay(Request $request): bool
    {
        return strcasecmp(trim($request->string('name')->toString()), 'Rest Day') === 0;
    }

    /**
     * @return array{ name: string, start_time: string|null, end_time: string|null, is_overnight: bool, required_employee_count: int, is_active: bool }
     */
    private function auditValues(ShiftTemplate $template): array
    {
        return [
            'name' => $template->name,
            'start_time' => $template->start_time,
            'end_time' => $template->end_time,
            'is_overnight' => (bool) $template->is_overnight,
            'required_employee_count' => (int) $template->required_employee_count,
            'is_active' => (bool) $template->is_active,
        ];
    }
}
