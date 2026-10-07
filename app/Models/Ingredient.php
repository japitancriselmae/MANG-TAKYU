<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $primaryKey = 'ingredient_id';

    protected $fillable = [
        'ingredient_name',
        'unit',
        'minimum_stock',
        'status',
    ];

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