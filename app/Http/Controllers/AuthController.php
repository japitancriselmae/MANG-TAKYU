<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\Attendance;
use App\Models\CookingBatch;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $email = strtolower(trim($credentials['email'] ?? ''));
        $password = $credentials['password'] ?? '';

        $key = 'login.'.$email.'.'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again in '.ceil($seconds / 60).' minute(s).',
            ])->onlyInput('email');
        }

        if (! Auth::attempt(['email' => $email, 'password' => $password], $request->boolean('remember'))) {
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

        if (in_array($user->employee->role->role_name, ['Cashier', 'Kitchen Staff'], true)) {
            $now = now();
            $todayShifts = Schedule::query()
                ->where('employee_id', $user->employee_id)
                ->whereDate('schedule_date', $now->toDateString())
                ->where('status', 'Active')
                ->orderBy('start_time')
                ->get();
            $availableShift = $todayShifts
                ->first(fn (Schedule $schedule): bool => $schedule->end_time > $now->format('H:i:s'));

            if (! $availableShift) {
                Auth::logout();

                $message = $todayShifts->isEmpty()
                    ? 'No active shift is scheduled for today. Please contact your manager.'
                    : 'Your shift has ended. You cannot log in after your scheduled end time.';

                return back()
                    ->withErrors(['email' => $message])
                    ->onlyInput('email');
            }

            $scheduleStart = Carbon::parse($now->toDateString().' '.$availableShift->start_time);
            $lateMinutes = $now->greaterThan($scheduleStart)
                ? (int) $scheduleStart->diffInMinutes($now)
                : 0;

            $todayAttendance = Attendance::query()
                ->where('employee_id', $user->employee_id)
                ->whereDate('date', $now->toDateString())
                ->first();

            if (! $todayAttendance) {
                Attendance::create([
                    'employee_id' => $user->employee_id,
                    'date' => $now->toDateString(),
                    'schedule_id' => $availableShift->schedule_id,
                    'time_in' => $now->format('H:i:s'),
                    'time_out' => null,
                    'status' => $lateMinutes > 0 ? 'Late' : 'Present',
                    'late_minutes' => $lateMinutes,
                    'undertime_minutes' => 0,
                    'proof_image' => null,
                ]);
            }
        }

        return redirect()->intended('/');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user instanceof User && in_array($user->employee?->role?->role_name, ['Cashier', 'Kitchen Staff'], true)) {
            $now = now();
            $attendance = Attendance::query()
                ->with('schedule')
                ->where('employee_id', $user->employee_id)
                ->whereDate('date', $now->toDateString())
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->first();

            if ($attendance) {
                $scheduleEnd = $attendance->schedule
                    ? Carbon::parse($attendance->date->format('Y-m-d').' '.$attendance->schedule->end_time)
                    : null;

                $undertimeMinutes = $scheduleEnd && $now->lessThan($scheduleEnd)
                    ? (int) $now->diffInMinutes($scheduleEnd)
                    : 0;

                $attendance->update([
                    'time_out' => $now->format('H:i:s'),
                    'undertime_minutes' => $undertimeMinutes,
                ]);

                // Reset the kitchen queue when the shift ends: remove any
                // unfinished cooking batch started by this employee so it does
                // not linger in the kitchen queue after their shift is done.
                CookingBatch::query()
                    ->where('employee_id', $user->employee_id)
                    ->where('status', 'cooking')
                    ->delete();
            }
        }

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
