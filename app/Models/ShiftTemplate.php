<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftTemplate extends Model
{
    protected $primaryKey = 'shift_template_id';

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'is_overnight',
        'required_employee_count',
        'is_active',
    ];

    protected $casts = [
        'is_overnight' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scheduleAssignments(): HasMany
    {
        return $this->hasMany(
            Schedule::class,
            'shift_template_id',
            'shift_template_id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
