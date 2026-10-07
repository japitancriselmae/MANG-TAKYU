<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'employee_id',
        'schedule_date',
        'start_time',
        'end_time',
        'status',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'employee_id',
            'employee_id'
        );
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(
            Attendance::class,
            'schedule_id',
            'schedule_id'
        );
    }
}
