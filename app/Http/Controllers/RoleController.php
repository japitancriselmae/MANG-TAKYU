<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Admin can VIEW but not modify roles.
     * Only Manager can manage roles.
     */
    private function requireManager(): void
    {
        $role = auth()->user()->employee?->role?->role_name;

        if ($role !== 'Manager') {
            abort(403, 'Only the Manager can manage roles.');
        }
    }

    public function index()
    {
        // Hide the Admin system role
        $roles = Role::withCount('employees')
            ->where('role_name', '!=', 'Admin')
            ->orderBy('role_id')
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $this->requireManager();

        return view('roles.create');
    }

    public function store(Request $request, ActivityLogger $activityLogger)
    {
        $this->requireManager();

        $validated = $request->validate([
            'role_name' => [
                'required',
                'string',
                'max:255',
                'unique:roles,role_name',
                function ($attribute, $value, $fail) {
                    if (strtolower(trim($value)) === 'admin') {
                        $fail('The "Admin" role is a protected system role and cannot be created.');
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($validated, $activityLogger): void {
            $role = Role::create($validated);

            $activityLogger->record(
                action: 'role.created',
                module: 'administration',
                description: 'Role created.',
                subject: $role,
                newValues: ['role_name' => $role->role_name],
            );
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function show(Role $role)
    {
        if ($role->role_name === 'Admin') {
            return redirect()
                ->route('roles.index')
                ->withErrors(['role' => 'The Admin role is a protected system role.']);
        }

        $role->loadCount('employees');

        return view('roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        $this->requireManager();

        if ($role->role_name === 'Admin') {
            return redirect()
                ->route('roles.index')
                ->withErrors(['role' => 'The Admin role is a protected system role and cannot be edited.']);
        }

        return view('roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role, ActivityLogger $activityLogger)
    {
        $this->requireManager();

        if ($role->role_name === 'Admin') {
            return redirect()
                ->route('roles.index')
                ->withErrors(['role' => 'The Admin role is a protected system role and cannot be edited.']);
        }

        $validated = $request->validate([
            'role_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'role_name')->ignore($role->role_id, 'role_id'),
                function ($attribute, $value, $fail) {
                    if (strtolower(trim($value)) === 'admin') {
                        $fail('The "Admin" role is a protected system role and cannot be used.');
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($validated, $role, $activityLogger): void {
            $role = Role::query()
                ->whereKey($role->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $oldValues = ['role_name' => $role->role_name];
            $role->update($validated);

            $activityLogger->record(
                action: 'role.updated',
                module: 'administration',
                description: 'Role information updated.',
                subject: $role,
                oldValues: $oldValues,
                newValues: ['role_name' => $role->role_name],
            );
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role, ActivityLogger $activityLogger)
    {
        $this->requireManager();

        if ($role->role_name === 'Admin') {
            return redirect()
                ->route('roles.index')
                ->withErrors(['role' => 'The Admin role is a protected system role and cannot be deleted.']);
        }

        if ($role->employees()->exists()) {
            return back()->withErrors([
                'role' => 'This role cannot be deleted because employees are assigned to it. Remove or reassign those employees first.',
            ]);
        }

        DB::transaction(function () use ($role, $activityLogger): void {
            $oldValues = ['role_name' => $role->role_name];
            $role->delete();

            $activityLogger->record(
                action: 'role.deleted',
                module: 'administration',
                description: 'Role deleted.',
                subject: $role,
                oldValues: $oldValues,
            );
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
