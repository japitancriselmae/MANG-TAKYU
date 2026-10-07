<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $primaryKey = 'inventory_id';

    protected $fillable = [
        'ingredient_id',
        'quantity',
        'minimum_stock',
        'last_updated',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'minimum_stock' => 'decimal:2',
            'last_updated' => 'datetime',
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

    public function isLowStock(): bool
    {
        return (float) $this->quantity > 0
            && (float) $this->quantity <= (float) $this->minimum_stock;
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->quantity <= 0;
    }
}
