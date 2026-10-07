<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showForm()
    {
        if (User::count() > 0) {
            return redirect()->route('login')->withErrors([
                'email' => 'Setup is already complete. Please ask your Admin to create your account.',
            ]);
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        if (User::count() > 0) {
            return redirect()->route('login')->withErrors([
                'email' => 'Setup is already complete.',
            ]);
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated) {

            // First account = ADMIN
            $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);

            $parts = preg_split('/\s+/', trim($validated['name']), 2);
            $first = $parts[0] ?? $validated['name'];
            $last  = $parts[1] ?? '';

            $employee = Employee::create([
                'role_id'    => $adminRole->role_id,
                'first_name' => $first,
                'last_name'  => $last,
                'status'     => 'Active',
            ]);

            return User::create([
                'name'        => $validated['name'],
                'email'       => $validated['email'],
                'password'    => Hash::make($validated['password']),
                'employee_id' => $employee->employee_id,
            ]);
        });

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Welcome! Your Admin account is ready.');
    }
}