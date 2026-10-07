<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use Illuminate\Http\Request;

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
        $employees = Employee::with('role')
            ->whereHas('role', fn($q) => $q->where('role_name', '!=', 'Admin'))
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

    public function store(Request $request)
    {
        $this->requireManager();

        $validated = $request->validate([
            'role_id'    => ['required', 'exists:roles,role_id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'status'     => ['required', 'in:Active,Inactive'],
        ]);

        Employee::create($validated);

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

        $employee->load('role');

        return view('employees.edit', compact('employee', 'roles'));
    }

    public function update(Request $request, Employee $employee)
    {
        $this->requireManager();

        if (($employee->role?->role_name ?? '') === 'Admin') {
            return redirect()
                ->route('employees.index')
                ->withErrors(['employee' => 'The Admin employee record is protected.']);
        }

        $validated = $request->validate([
            'role_id'    => ['required', 'exists:roles,role_id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'status'     => ['required', 'in:Active,Inactive'],
        ]);

        $employee->update($validated);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $this->requireManager();

        if (($employee->role?->role_name ?? '') === 'Admin') {
            return redirect()
                ->route('employees.index')
                ->withErrors(['employee' => 'The Admin employee record is protected.']);
        }

        if ($employee->user()->exists()) {
            return back()->withErrors([
                'employee' => 'This employee cannot be deleted because it has a user account.',
            ]);
        }

        if ($employee->orders()->exists()) {
            return back()->withErrors([
                'employee' => 'This employee cannot be deleted because it has existing orders.',
            ]);
        }

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }
}