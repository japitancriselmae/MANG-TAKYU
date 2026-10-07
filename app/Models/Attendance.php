<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $primaryKey = 'attendance_id';

    protected $fillable = [
    'employee_id',
    'schedule_id',
    'date',
    'time_in',
    'time_out',
    'status',
    'late_minutes',
    'undertime_minutes',
    'proof_image',
    'approval_status',
    'approved_by',
    'approved_at',
    'rejection_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'late_minutes' => 'integer',
        'undertime_minutes' => 'integer',
    ];

    /**
     * Employee connected to this attendance.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'employee_id',
            'employee_id'
        );
    }

    /**
     * Schedule connected to this attendance.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(
            Schedule::class,
            'schedule_id',
            'schedule_id'
        );
    }
}
