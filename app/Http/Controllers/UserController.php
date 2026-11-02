<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Which roles can the CURRENT user create/edit accounts for?
     *   Admin   → Manager only
     *   Manager → Cashier, Kitchen Staff only
     */
    private function allowedRoles(): array
    {
        $role = auth()->user()->employee?->role?->role_name;

        return match ($role) {
            'Admin' => ['Manager'],
            'Manager' => ['Cashier', 'Kitchen Staff'],
            default => [],
        };
    }

    /**
     * Can the CURRENT user create/edit this specific user?
     */
    private function canManage(User $target): bool
    {
        $authRole = auth()->user()->employee?->role?->role_name;
        $targetRole = $target->employee?->role?->role_name;

        // Never touch Admin
        if ($targetRole === 'Admin') {
            return false;
        }

        // Never touch yourself
        if ($target->id === auth()->id()) {
            return false;
        }

        return in_array($targetRole, $this->allowedRoles());
    }

    public function index()
    {
        $authRole = auth()->user()->employee?->role?->role_name;
        $allowed = $this->allowedRoles();

        // Everyone with a user account, except the Admin
        $query = User::with('employee.role')
            ->whereHas('employee.role', fn ($q) => $q->where('role_name', '!=', 'Admin'))
            ->orderBy('id');

        // Manager only sees their own staff + themselves
        if ($authRole === 'Manager') {
            $query->where(function ($q) use ($allowed) {
                $q->whereHas('employee.role', fn ($r) => $r->whereIn('role_name', $allowed))
                    ->orWhere('id', auth()->id());
            });
        }

        // Admin sees everyone (Managers + Staff)

        $users = $query->get();

        return view('users.index', compact('users', 'allowed'));
    }

    public function create()
    {
        $allowed = $this->allowedRoles();

        if (empty($allowed)) {
            abort(403, 'You are not authorized to create user accounts.');
        }

        $employees = Employee::with('role')
            ->whereDoesntHave('user')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', $allowed))
            ->orderBy('first_name')
            ->get();
        $selectedEmployeeId = request()->query('employee_id');

        return view('users.create', compact('employees', 'allowed', 'selectedEmployeeId'));
    }

    public function store(Request $request, ActivityLogger $activityLogger)
    {
        $allowed = $this->allowedRoles();

        if (empty($allowed)) {
            abort(403, 'You are not authorized to create user accounts.');
        }

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'exists:employees,employee_id',
                function ($attribute, $value, $fail) use ($allowed) {
                    $employee = Employee::with('role')->find($value);

                    if (! $employee) {
                        $fail('Employee not found.');

                        return;
                    }

                    $roleName = $employee->role->role_name ?? '';

                    if ($roleName === 'Admin') {
                        $fail('An Admin account already exists. Only one Admin is allowed.');

                        return;
                    }

                    if (! in_array($roleName, $allowed)) {
                        $fail('You are not allowed to create an account for this role.');

                        return;
                    }

                    if (User::where('employee_id', $value)->exists()) {
                        $fail('This employee already has a user account.');
                    }
                },
            ],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        DB::transaction(function () use ($employee, $validated, $activityLogger): void {
            $user = User::create([
                'name' => trim($employee->first_name.' '.$employee->last_name),
                'email' => strtolower($validated['email']),
                'password' => Hash::make($validated['password']),
                'employee_id' => $employee->employee_id,
            ]);

            $activityLogger->record(
                action: 'user.created',
                module: 'users',
                description: 'User account created.',
                subject: $user,
                newValues: $this->auditValues($user),
            );
        });

        return redirect()
            ->route('employees.index')
            ->with('success', 'User account created successfully.');
    }

    public function show(User $user)
    {
        $user->load('employee.role');

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        if (! $this->canManage($user)) {
            abort(403, 'You are not authorized to edit this account.');
        }

        $user->load('employee.role');

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user, ActivityLogger $activityLogger)
    {
        if (! $this->canManage($user)) {
            abort(403, 'You are not authorized to edit this account.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $oldValues = $this->auditValues($user);
        $passwordChanged = ! empty($validated['password']);

        DB::transaction(function () use ($user, $validated, $passwordChanged, $oldValues, $activityLogger): void {
            $user->name = $validated['name'];
            $user->email = strtolower($validated['email']);

            if ($passwordChanged) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            $activityLogger->record(
                action: 'user.updated',
                module: 'users',
                description: 'User account information updated.',
                subject: $user,
                oldValues: $oldValues,
                newValues: [...$this->auditValues($user), 'password_changed' => $passwordChanged],
            );
        });

        return redirect()
            ->route('employees.index')
            ->with('success', 'User account updated successfully.');
    }

    public function destroy(User $user, ActivityLogger $activityLogger)
    {
        if (! $this->canManage($user)) {
            abort(403, 'You are not authorized to delete this account.');
        }

        DB::transaction(function () use ($user, $activityLogger): void {
            $oldValues = $this->auditValues($user);
            $user->delete();

            $activityLogger->record(
                action: 'user.deleted',
                module: 'users',
                description: 'User account deleted.',
                subject: $user,
                oldValues: $oldValues,
            );
        });

        return redirect()
            ->route('employees.index')
            ->with('success', 'User account deleted successfully.');
    }

    /**
     * @return array{ name: string, employee_id: int|null, role: string|null }
     */
    private function auditValues(User $user): array
    {
        $user->loadMissing('employee.role');

        return [
            'name' => $user->name,
            'employee_id' => $user->employee_id,
            'role' => $user->employee?->role?->role_name,
        ];
    }
}
