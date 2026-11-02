<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function ingredients(): HasMany
    {
        return $this->hasMany(
            Ingredient::class,
            'inventory_category_id',
            'id'
        );
    }
}
