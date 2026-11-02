<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Schedule extends Model
{
    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'employee_id',
        'schedule_date',
        'start_time',
        'end_time',
        'status',
        'week_start_date',
        'week_end_date',
        'schedule_status',
        'shift_template_id',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function setScheduleDateAttribute(mixed $value): void
    {
        $this->attributes['schedule_date'] = Carbon::parse($value)->toDateString();
    }

    const STATUS_DRAFT = 'draft';

    const STATUS_APPROVED = 'approved';

    const STATUS_PUBLISHED = 'published';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'employee_id',
            'employee_id'
        );
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(
            ShiftTemplate::class,
            'shift_template_id',
            'shift_template_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'created_by',
            'employee_id'
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'approved_by',
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

    public function startsAt(): ?Carbon
    {
        if (! $this->schedule_date || ! $this->start_time) {
            return null;
        }

        return Carbon::parse($this->schedule_date->toDateString().' '.$this->start_time);
    }

    public function endsAt(): ?Carbon
    {
        $start = $this->startsAt();
        if (! $start || ! $this->end_time) {
            return null;
        }

        $end = Carbon::parse($this->schedule_date->toDateString().' '.$this->end_time);
        if (! $end->greaterThan($start)) {
            $end->addDay();
        }

        return $end;
    }

    public function scopeDraft($query)
    {
        return $query->where('schedule_status', self::STATUS_DRAFT);
    }

    public function scopeApproved($query)
    {
        return $query->where('schedule_status', self::STATUS_APPROVED);
    }

    public function scopePublished($query)
    {
        return $query->where('schedule_status', self::STATUS_PUBLISHED);
    }

    public function scopeForWeek($query, $weekStart, $weekEnd)
    {
        return $query
            ->whereDate('schedule_date', '>=', Carbon::parse($weekStart)->toDateString())
            ->whereDate('schedule_date', '<=', Carbon::parse($weekEnd)->toDateString());
    }
}
