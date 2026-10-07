<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
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
            'Admin'   => ['Manager'],
            'Manager' => ['Cashier', 'Kitchen Staff'],
            default   => [],
        };
    }

    /**
     * Can the CURRENT user create/edit this specific user?
     */
    private function canManage(User $target): bool
    {
        $authRole   = auth()->user()->employee?->role?->role_name;
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
        $allowed  = $this->allowedRoles();

        // Everyone with a user account, except the Admin
        $query = User::with('employee.role')
            ->whereHas('employee.role', fn($q) => $q->where('role_name', '!=', 'Admin'))
            ->orderBy('id');

        // Manager only sees their own staff + themselves
        if ($authRole === 'Manager') {
            $query->where(function ($q) use ($allowed) {
                $q->whereHas('employee.role', fn($r) => $r->whereIn('role_name', $allowed))
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
            ->whereHas('role', fn($q) => $q->whereIn('role_name', $allowed))
            ->orderBy('first_name')
            ->get();

        return view('users.create', compact('employees', 'allowed'));
    }

    public function store(Request $request)
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

                    if (!$employee) {
                        $fail('Employee not found.');
                        return;
                    }

                    $roleName = $employee->role->role_name ?? '';

                    if ($roleName === 'Admin') {
                        $fail('An Admin account already exists. Only one Admin is allowed.');
                        return;
                    }

                    if (!in_array($roleName, $allowed)) {
                        $fail('You are not allowed to create an account for this role.');
                        return;
                    }

                    if (User::where('employee_id', $value)->exists()) {
                        $fail('This employee already has a user account.');
                    }
                },
            ],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        User::create([
            'name'        => trim($employee->first_name . ' ' . $employee->last_name),
            'email'       => strtolower($validated['email']),
            'password'    => Hash::make($validated['password']),
            'employee_id' => $employee->employee_id,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User account created successfully.');
    }

    public function show(User $user)
    {
        $user->load('employee.role');

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        if (!$this->canManage($user)) {
            abort(403, 'You are not authorized to edit this account.');
        }

        $user->load('employee.role');

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if (!$this->canManage($user)) {
            abort(403, 'You are not authorized to edit this account.');
        }

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $user->name  = $validated['name'];
        $user->email = strtolower($validated['email']);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'User account updated successfully.');
    }

    public function destroy(User $user)
    {
        if (!$this->canManage($user)) {
            abort(403, 'You are not authorized to delete this account.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User account deleted successfully.');
    }
}