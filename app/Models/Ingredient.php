<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $primaryKey = 'ingredient_id';

    protected $fillable = [
        'inventory_category_id',
        'ingredient_name',
        'sku',
        'description',
        'unit',
        'minimum_stock',
        'purchase_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'minimum_stock' => 'decimal:2',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function inventoryCategory(): BelongsTo
    {
        return $this->belongsTo(
            InventoryCategory::class,
            'inventory_category_id'
        );
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(
            Inventory::class,
            'ingredient_id',
            'ingredient_id'
        );
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(
            StockMovement::class,
            'ingredient_id',
            'ingredient_id'
        );
    }

    public function productIngredients(): HasMany
    {
        return $this->hasMany(
            ProductIngredient::class,
            'ingredient_id',
            'ingredient_id'
        );
    }
}
