<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

#[UseEloquentBuilder(ActivityLogBuilder::class)]
class ActivityLog extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILURE = 'failure';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Activity logs are append-only.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Activity logs are append-only.');
        });
    }

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
