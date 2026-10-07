<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $primaryKey = 'employee_id';

    protected $fillable = [
        'role_id',
        'first_name',
        'last_name',
        'status',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(
            Role::class,
            'role_id',
            'role_id'
        );
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(
            Schedule::class,
            'employee_id',
            'employee_id'
        );
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(
            Attendance::class,
            'employee_id',
            'employee_id'
        );
    }

    public function orders(): HasMany
    {
        return $this->hasMany(
            Order::class,
            'employee_id',
            'employee_id'
        );
    }

    public function user(): HasOne
    {
        return $this->hasOne(
            User::class,
            'employee_id',
            'employee_id'
        );
    }
}
