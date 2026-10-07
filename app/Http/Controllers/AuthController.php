<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $email = strtolower(trim($credentials['email'] ?? ''));
        $password = $credentials['password'] ?? '';

        $key = 'login.' . $email . '.' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minute(s).',
            ])->onlyInput('email');
        }

        if (!Auth::attempt(['email' => $email, 'password' => $password], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            return back()
                ->withErrors([
                    'email' => 'The provided credentials are incorrect.',
                ])
                ->onlyInput('email');
        }

        RateLimiter::clear($key);

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user || ! $user->employee) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'This account is not linked to an employee.',
            ]);
        }

        if ($user->employee->status !== 'Active') {
            Auth::logout();

            return back()->withErrors([
                'email' => 'This employee account is inactive.',
            ]);
        }

        if (! $user->employee->role) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'This account does not have a valid role.',
            ]);
        }

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
