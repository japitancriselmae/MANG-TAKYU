<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->employee?->role?->role_name;

        $isManagerLike = in_array($role, ['Manager', 'Admin']);

        if ($isManagerLike) {
            $attendance = Attendance::with(['employee.role', 'schedule'])
                ->orderByDesc('date')
                ->orderByDesc('time_in')
                ->get();
        } else {
            $attendance = Attendance::with(['employee', 'schedule'])
                ->where('employee_id', $user->employee_id)
                ->orderByDesc('date')
                ->orderByDesc('time_in')
                ->get();
        }

        $todaySchedule = Schedule::where('employee_id', $user->employee_id)
            ->whereDate('schedule_date', Carbon::today())
            ->where('status', 'Active')
            ->orderBy('start_time')
            ->first();

        $todayAttendance = Attendance::where('employee_id', $user->employee_id)
            ->whereDate('date', Carbon::today())
            ->first();

        return view('attendances.index', compact(
            'attendance',
            'todaySchedule',
            'todayAttendance',
            'role',
            'isManagerLike'
        ));
    }

    public function create()
    {
        return redirect()->route('attendances.index');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user->employee_id) {
            return back()->withErrors([
                'attendance' => 'Your account is not linked to an employee.',
            ]);
        }

        $employeeId = $user->employee_id;
        $today = Carbon::today();

        $schedule = Schedule::where('employee_id', $employeeId)
            ->whereDate('schedule_date', $today)
            ->where('status', 'Active')
            ->orderBy('start_time')
            ->first();

        if (!$schedule) {
            return back()->withErrors([
                'attendance' => 'You do not have an active schedule for today.',
            ]);
        }

        $existing = Attendance::where('employee_id', $employeeId)
            ->whereDate('date', $today)
            ->first();

        if ($existing) {
            return back()->withErrors([
                'attendance' => 'You already have an attendance record for today.',
            ]);
        }

        $now = now();

        $scheduleStart = Carbon::parse(
            $today->format('Y-m-d') . ' ' . $schedule->start_time
        );

        $lateMinutes = 0;
        if ($now->greaterThan($scheduleStart)) {
            $lateMinutes = $scheduleStart->diffInMinutes($now);
        }

        Attendance::create([
            'employee_id'       => $employeeId,
            'schedule_id'       => $schedule->schedule_id,
            'date'              => $today->format('Y-m-d'),
            'time_in'           => $now->format('H:i:s'),
            'time_out'          => null,
            'status'            => $lateMinutes > 0 ? 'Late' : 'Present',
            'late_minutes'      => $lateMinutes,
            'undertime_minutes' => 0,
            'proof_image'       => null,
        ]);

        return redirect()
            ->route('attendances.index')
            ->with('success', 'Clock-in recorded. Please upload your proof of attendance photo.');
    }

    /**
     * Upload / re-upload proof photo for TODAY's attendance.
     */
    public function uploadProof(Request $request, Attendance $attendance)
    {
        $this->authorizeAttendance($attendance);

        if ($attendance->employee_id !== auth()->user()->employee_id) {
            abort(403, 'You can only upload your own attendance proof.');
        }

        $request->validate([
            'proof_image' => ['required', 'file', 'max:5120'],
        ], [
            'proof_image.required' => 'Please select a photo to upload.',
            'proof_image.max'      => 'The photo must be smaller than 5 MB.',
        ]);

        // Delete old photo using native PHP (no Storage facade = no finfo)
            if ($attendance->proof_image) {
                $path = storage_path('app/public/' . $attendance->proof_image);
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

        // Ensure target directory exists
        $targetDir = storage_path('app/public/attendance_proofs');
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Manually build filename
        $file     = $request->file('proof_image');
        $ext      = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $basename = uniqid('proof_', true) . '_' . time() . '.' . $ext;
        $path     = 'attendance_proofs/' . $basename;

        // Move file using native PHP
        $file->move($targetDir, $basename);

        $attendance->update([
            'proof_image' => $path,
        ]);

        return redirect()
            ->route('attendances.index')
            ->with('success', 'Proof photo uploaded successfully.');
    }

    public function clockOut(Attendance $attendance)
    {
        $this->authorizeAttendance($attendance);

        if (!$attendance->time_in) {
            return back()->withErrors(['attendance' => 'You must clock in before you can clock out.']);
        }

        if ($attendance->time_out) {
            return back()->withErrors(['attendance' => 'You have already clocked out.']);
        }

        if (!$attendance->schedule) {
            return back()->withErrors(['attendance' => 'This attendance record does not have a schedule.']);
        }

        $now = now();

        $scheduleEnd = Carbon::parse(
            Carbon::parse($attendance->date)->format('Y-m-d')
                . ' '
                . $attendance->schedule->end_time
        );

        $undertimeMinutes = 0;
        if ($now->lessThan($scheduleEnd)) {
            $undertimeMinutes = $now->diffInMinutes($scheduleEnd);
        }

        $attendance->update([
            'time_out'          => $now->format('H:i:s'),
            'undertime_minutes' => $undertimeMinutes,
        ]);

        return redirect()
            ->route('attendances.index')
            ->with('success', 'Clock out recorded successfully.');
    }

    public function show(Attendance $attendance)
    {
        $this->authorizeAttendance($attendance);

        $attendance->load(['employee.role', 'schedule']);

        return view('attendances.show', compact('attendance'));
    }

    public function edit(Attendance $attendance)
    {
        $this->authorizeAttendance($attendance);

        $role = auth()->user()->employee?->role?->role_name;

        if (!in_array($role, ['Manager', 'Admin'])) {
            abort(403, 'Only the Manager can edit attendance records.');
        }

        $attendance->load(['employee', 'schedule']);

        return view('attendances.edit', compact('attendance'));
    }

    public function update(Request $request, Attendance $attendance)
    {
        $this->authorizeAttendance($attendance);

        $role = auth()->user()->employee?->role?->role_name;

        if (!in_array($role, ['Manager', 'Admin'])) {
            abort(403, 'Only the Manager can update attendance records.');
        }

        $validated = $request->validate([
            'time_in'           => ['nullable', 'date_format:H:i:s'],
            'time_out'          => ['nullable', 'date_format:H:i:s', 'after_or_equal:time_in'],
            'status'            => ['required', 'string', 'max:50'],
            'late_minutes'      => ['required', 'integer', 'min:0'],
            'undertime_minutes' => ['required', 'integer', 'min:0'],
        ]);

        $attendance->update($validated);

        return redirect()
            ->route('attendances.index')
            ->with('success', 'Attendance updated successfully.');
    }

    public function destroy(Attendance $attendance)
    {
        $role = auth()->user()->employee?->role?->role_name;

        if (!in_array($role, ['Manager', 'Admin'])) {
            abort(403, 'Only the Manager can delete attendance records.');
        }

        if ($attendance->proof_image) {
            Storage::disk('public')->delete($attendance->proof_image);
        }

        $attendance->delete();

        return redirect()
            ->route('attendances.index')
            ->with('success', 'Attendance record deleted successfully.');
    }

    private function authorizeAttendance(Attendance $attendance): void
    {
        $user = auth()->user();
        $role = $user->employee?->role?->role_name;

        if (in_array($role, ['Manager', 'Admin'])) {
            return;
        }

        if ($attendance->employee_id !== $user->employee_id) {
            abort(403, 'You are not authorized to access this attendance record.');
        }
    }
}