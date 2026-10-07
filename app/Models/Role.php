<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $primaryKey = 'role_id';

    protected $fillable = [
        'role_name',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(
            Employee::class,
            'role_id',
            'role_id'
        );
    }
}