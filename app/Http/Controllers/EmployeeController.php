<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Admin can VIEW but cannot modify employees.
     * Only Manager can manage the employee list.
     */
    private function requireManager(): void
    {
        $role = auth()->user()->employee?->role?->role_name;

        if ($role !== 'Manager') {
            abort(403, 'Only the Manager can manage employees.');
        }
    }

    public function index()
    {
        $employees = Employee::with(['role', 'user'])
            ->whereHas('role', fn ($q) => $q->where('role_name', '!=', 'Admin'))
            ->orderBy('employee_id')
            ->get();

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        $this->requireManager();

        $roles = Role::where('role_name', '!=', 'Admin')
            ->orderBy('role_id')
            ->get();

        return view('employees.create', compact('roles'));
    }

    public function store(Request $request, ActivityLogger $activityLogger)
    {
        $this->requireManager();

        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,role_id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        DB::transaction(function () use ($validated, $activityLogger): void {
            $employee = Employee::create($validated);

            $activityLogger->record(
                action: 'employee.created',
                module: 'employees',
                description: 'Employee created.',
                subject: $employee,
                newValues: $this->auditValues($employee),
            );
        });

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['role', 'schedules', 'attendances', 'user']);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $this->requireManager();

        // Cannot edit the Admin's own employee record
        if (($employee->role?->role_name ?? '') === 'Admin') {
            return redirect()
                ->route('employees.index')
                ->withErrors(['employee' => 'The Admin employee record is protected.']);
        }

        $roles = Role::where('role_name', '!=', 'Admin')
            ->orderBy('role_id')
            ->get();

        $employee->load(['role', 'user']);

        return view('employees.edit', compact('employee', 'roles'));
    }

    public function update(Request $request, Employee $employee, ActivityLogger $activityLogger)
    {
        $this->requireManager();

        if (($employee->role?->role_name ?? '') === 'Admin') {
            return redirect()
                ->route('employees.index')
                ->withErrors(['employee' => 'The Admin employee record is protected.']);
        }

        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,role_id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive'],
        ]);

        DB::transaction(function () use ($validated, $employee, $activityLogger): void {
            $employee = Employee::query()
                ->whereKey($employee->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $oldValues = $this->auditValues($employee);
            $employee->update($validated);
            $employee->load('role');
            $newValues = $this->auditValues($employee);

            $activityLogger->record(
                action: $oldValues['status'] === 'Active' && $newValues['status'] === 'Inactive'
                    ? 'employee.deactivated'
                    : 'employee.updated',
                module: 'employees',
                description: 'Employee information updated.',
                subject: $employee,
                oldValues: $oldValues,
                newValues: $newValues,
            );
        });

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee, ActivityLogger $activityLogger)
    {
        $this->requireManager();

        if (($employee->role?->role_name ?? '') === 'Admin') {
            return redirect()
                ->route('employees.index')
                ->withErrors(['employee' => 'The Admin employee record is protected.']);
        }

        if ($employee->employee_id === auth()->user()->employee_id) {
            return back()->withErrors([
                'employee' => 'You cannot delete your own employee record.',
            ]);
        }

        $roleName = $employee->role?->role_name;

        if (
            $employee->user()->exists()
            && ! in_array($roleName, ['Cashier', 'Kitchen Staff'], true)
        ) {
            return back()->withErrors([
                'employee' => 'You are not authorized to delete this employee account.',
            ]);
        }

        if ($employee->orders()->exists()) {
            return back()->withErrors([
                'employee' => 'This employee cannot be deleted because it has existing orders.',
            ]);
        }

        if ($employee->schedules()->exists() || $employee->attendances()->exists()) {
            return back()->withErrors([
                'employee' => 'This employee cannot be deleted because they have schedules or attendance records.',
            ]);
        }

        DB::transaction(function () use ($employee, $activityLogger): void {
            $employee = Employee::query()
                ->whereKey($employee->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $oldValues = $this->auditValues($employee);

            $user = $employee->user()->lockForUpdate()->first();

            if ($user) {
                $userValues = [
                    'name' => $user->name,
                    'employee_id' => $employee->employee_id,
                    'role' => $employee->role?->role_name,
                ];
                $user->delete();

                $activityLogger->record(
                    action: 'user.deleted',
                    module: 'users',
                    description: 'Employee login account deleted with employee record.',
                    subject: $user,
                    oldValues: $userValues,
                );
            }

            $employee->delete();

            $activityLogger->record(
                action: 'employee.deleted',
                module: 'employees',
                description: 'Employee deleted.',
                subject: $employee,
                oldValues: $oldValues,
            );
        });

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Employee $employee): array
    {
        $employee->loadMissing('role');

        return [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'role' => $employee->role?->role_name,
            'status' => $employee->status,
        ];
    }
}
