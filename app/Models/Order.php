<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $primaryKey = 'order_id';

    protected $fillable = [
        'employee_id',
        'order_date',
        'order_type',
        'is_fresh_chicken',
        'estimated_ready_at',
        'ready_at',
        'total_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'datetime',
            'is_fresh_chicken' => 'boolean',
            'estimated_ready_at' => 'datetime',
            'ready_at' => 'datetime',
            'total_amount' => 'decimal:2',
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

    public function orderItems(): HasMany
    {
        return $this->hasMany(
            OrderItem::class,
            'order_id',
            'order_id'
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            Payment::class,
            'order_id',
            'order_id'
        );
    }
}
