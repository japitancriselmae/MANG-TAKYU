<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $primaryKey = 'movement_id';

    protected $fillable = [
        'ingredient_id',
        'movement_type',
        'quantity',
        'movement_date',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'movement_date' => 'datetime',
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(
            Ingredient::class,
            'ingredient_id',
            'ingredient_id'
        );
    }
}
