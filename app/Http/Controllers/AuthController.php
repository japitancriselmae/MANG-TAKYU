<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\CookingBatch;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public function login(LoginRequest $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $email = strtolower(trim($credentials['email'] ?? ''));
        $password = $credentials['password'] ?? '';

        $key = 'login.'.$email.'.'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->recordFailedLogin($activityLogger, $email);

            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again in '.ceil($seconds / 60).' minute(s).',
            ])->onlyInput('email');
        }

        if (! Auth::attempt(['email' => $email, 'password' => $password], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            $this->recordFailedLogin($activityLogger, $email);

            return back()
                ->withErrors([
                    'email' => 'The provided credentials are incorrect.',
                ])
                ->onlyInput('email');
        }

        RateLimiter::clear($key);

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user instanceof User || ! $user->employee) {
            $this->recordFailedLogin(
                $activityLogger,
                $email,
                $user instanceof User ? $user : null,
                'Login rejected because the account is not linked to an employee.',
            );
            Auth::logout();

            return back()->withErrors([
                'email' => 'This account is not linked to an employee.',
            ]);
        }

        if ($user->employee->status !== 'Active') {
            $this->recordFailedLogin(
                $activityLogger,
                $email,
                $user,
                'Login rejected because the employee account is inactive.',
            );
            Auth::logout();

            return back()->withErrors([
                'email' => 'This employee account is inactive.',
            ]);
        }

        if (! $user->employee->role) {
            $this->recordFailedLogin(
                $activityLogger,
                $email,
                $user,
                'Login rejected because the account has no assigned role.',
            );
            Auth::logout();

            return back()->withErrors([
                'email' => 'This account does not have a valid role.',
            ]);
        }

        if (in_array($user->employee->role->role_name, ['Cashier', 'Kitchen Staff'], true)) {
            $now = now();
            $shiftCandidates = Schedule::query()
                ->where('employee_id', $user->employee_id)
                ->whereDate('schedule_date', '>=', $now->copy()->subDay()->toDateString())
                ->whereDate('schedule_date', '<=', $now->toDateString())
                ->where('status', 'Active')
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->orderBy('schedule_date')
                ->orderBy('start_time')
                ->get();
            $todayShifts = $shiftCandidates
                ->filter(fn (Schedule $schedule): bool => $schedule->schedule_date->toDateString() === $now->toDateString());
            $availableShift = $shiftCandidates
                ->first(fn (Schedule $schedule): bool => $schedule->endsAt()?->greaterThan($now) ?? false);

            if (! $availableShift) {
                $this->recordFailedLogin(
                    $activityLogger,
                    $email,
                    $user,
                    'Login rejected because no active shift is available.',
                );
                Auth::logout();

                $message = $todayShifts->isEmpty()
                    ? 'No active shift is scheduled for today. Please contact your manager.'
                    : 'Your shift has ended. You cannot log in after your scheduled end time.';

                return back()
                    ->withErrors(['email' => $message])
                    ->onlyInput('email');
            }

            $scheduleStart = $availableShift->startsAt();
            $lateMinutes = $scheduleStart && $now->greaterThan($scheduleStart)
                ? (int) $scheduleStart->diffInMinutes($now)
                : 0;

            $todayAttendance = Attendance::query()
                ->where('employee_id', $user->employee_id)
                ->where(function ($query) use ($now, $availableShift): void {
                    $query->whereDate('date', $now->toDateString())
                        ->orWhere('schedule_id', $availableShift->schedule_id);
                })
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

        $activityLogger->record(
            action: 'auth.login',
            module: 'authentication',
            description: 'User logged in successfully.',
            subject: $user,
        );

        return redirect()->intended('/');
    }

    public function logout(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = Auth::user();

        DB::transaction(function () use ($user, $activityLogger): void {
            if ($user instanceof User && in_array($user->employee?->role?->role_name, ['Cashier', 'Kitchen Staff'], true)) {
                $now = now();
                $attendance = Attendance::query()
                    ->with('schedule')
                    ->where('employee_id', $user->employee_id)
                    ->where(function (Builder $query) use ($now): void {
                        $query->whereDate('date', $now->toDateString())
                            ->orWhereDate('date', $now->copy()->subDay()->toDateString());
                    })
                    ->whereNotNull('time_in')
                    ->whereNull('time_out')
                    ->orderByDesc('date')
                    ->first();

                if ($attendance) {
                    $scheduleEnd = $attendance->schedule
                            ? $attendance->schedule->endsAt()
                        : null;

                    $undertimeMinutes = $scheduleEnd && $now->lessThan($scheduleEnd)
                        ? (int) $now->diffInMinutes($scheduleEnd)
                        : 0;

                    $attendance->update([
                        'time_out' => $now->format('H:i:s'),
                        'undertime_minutes' => $undertimeMinutes,
                    ]);

                    CookingBatch::query()
                        ->where('employee_id', $user->employee_id)
                        ->where('status', 'cooking')
                        ->delete();
                }
            }

            $activityLogger->record(
                action: 'auth.logout',
                module: 'authentication',
                description: 'User logged out.',
                actor: $user instanceof User ? $user : null,
            );
        });

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function recordFailedLogin(
        ActivityLogger $activityLogger,
        string $email,
        ?User $user = null,
        string $description = 'Login attempt failed.',
    ): void {
        $user ??= User::query()->where('email', $email)->first();

        $activityLogger->record(
            action: 'auth.login_failed',
            module: 'authentication',
            description: $description,
            status: ActivityLog::STATUS_FAILURE,
            actor: $user,
            actorName: $user?->name ?? 'Unknown account',
        );
    }
}
