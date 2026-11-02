<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

class ActivityLogBuilder extends Builder
{
    public function update(array $values): never
    {
        throw new LogicException('Activity logs are append-only.');
    }

    public function upsert(array $values, $uniqueBy, $update = null): never
    {
        throw new LogicException('Activity logs are append-only.');
    }

    public function updateOrInsert(array $attributes, array|callable $values = []): never
    {
        throw new LogicException('Activity logs are append-only.');
    }

    public function touch($column = null): never
    {
        throw new LogicException('Activity logs are append-only.');
    }

    public function increment($column, $amount = 1, array $extra = []): never
    {
        throw new LogicException('Activity logs are append-only.');
    }

    public function decrement($column, $amount = 1, array $extra = []): never
    {
        throw new LogicException('Activity logs are append-only.');
    }

    public function incrementEach(array $columns, array $extra = []): never
    {
        throw new LogicException('Activity logs are append-only.');
    }

    public function decrementEach(array $columns, array $extra = []): never
    {
        throw new LogicException('Activity logs are append-only.');
    }

    public function delete(): never
    {
        throw new LogicException('Activity logs are append-only.');
    }

    public function forceDelete(): never
    {
        throw new LogicException('Activity logs are append-only.');
    }

    public function truncate(): never
    {
        throw new LogicException('Activity logs are append-only.');
    }
}
