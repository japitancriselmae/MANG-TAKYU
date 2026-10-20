<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CookingBatch extends Model
{
    protected $fillable = [
        'employee_id',
        'food_type',
        'quantity',
        'duration_minutes',
        'status',
        'started_at',
        'ready_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'duration_minutes' => 'integer',
            'started_at' => 'datetime',
            'ready_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'employee_id',
            'employee_id'
        );
    }
}
