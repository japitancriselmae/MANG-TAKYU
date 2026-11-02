<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ActivityLogger
{
    /**
     * @var list<string>
     */
    private const SENSITIVE_KEY_PARTS = [
        'password',
        'token',
        'secret',
        'credential',
        'authorization',
        'cookie',
        'proof',
        'email',
        'phone',
        'mobile',
        'contact_number',
        'address',
        'birth_date',
        'date_of_birth',
        'national_id',
        'government_id',
        'ssn',
        'bank_account',
        'account_number',
        'card_number',
        'cvv',
        'cvc',
        'pin',
        'otp',
        'remember_token',
        'session_id',
    ];

    public function __construct(private Request $request) {}

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(
        string $action,
        string $module,
        string $description,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $status = ActivityLog::STATUS_SUCCESS,
        ?User $actor = null,
        ?string $actorName = null,
    ): ActivityLog {
        $action = trim($action);
        $module = trim($module);
        $description = trim($description);

        if ($action === '' || Str::length($action) > 100) {
            throw new InvalidArgumentException('Activity action must be between 1 and 100 characters.');
        }

        if ($module === '' || Str::length($module) > 100) {
            throw new InvalidArgumentException('Activity module must be between 1 and 100 characters.');
        }

        if ($description === '') {
            throw new InvalidArgumentException('Activity description cannot be empty.');
        }

        if (! in_array($status, [ActivityLog::STATUS_SUCCESS, ActivityLog::STATUS_FAILURE], true)) {
            throw new InvalidArgumentException('Activity status must be success or failure.');
        }

        if ($actor === null) {
            $authenticatedActor = Auth::user();
            $actor = $authenticatedActor instanceof User ? $authenticatedActor : null;
        }

        $actorName ??= $actor?->name ?? 'System';

        if (Str::length($actorName) > 255) {
            throw new InvalidArgumentException('Activity actor name cannot exceed 255 characters.');
        }

        $subjectId = $subject?->getKey();
        $userAgent = $this->request->userAgent();

        if ($subject && $subjectId === null) {
            throw new InvalidArgumentException('Activity subjects must already be persisted.');
        }

        $activityLog = new ActivityLog;
        $activityLog->forceFill([
            'user_id' => $actor?->getKey(),
            'actor_name' => $actorName,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subjectId === null ? null : (string) $subjectId,
            'old_values' => $oldValues === null ? null : $this->sanitizeValues($oldValues),
            'new_values' => $newValues === null ? null : $this->sanitizeValues($newValues),
            'status' => $status,
            'ip_address' => $this->request->ip(),
            'user_agent' => $userAgent === null ? null : Str::limit($userAgent, 1000, ''),
            'created_at' => now(),
        ]);
        $activityLog->saveOrFail();

        return $activityLog;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitizeValues(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            $key = (string) $key;

            if ($this->isSensitiveKey($key, $value)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            if ($value instanceof DateTimeInterface) {
                $sanitized[$key] = $value->format(DATE_ATOM);

                continue;
            }

            if ($value instanceof BackedEnum) {
                $sanitized[$key] = $value->value;

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeValues($value);

                continue;
            }

            if (! is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('Activity values must contain only scalar values or arrays.');
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key, mixed $value): bool
    {
        $normalizedKey = Str::lower(Str::snake($key));

        if (is_bool($value) && str_ends_with($normalizedKey, '_changed')) {
            return false;
        }

        foreach (self::SENSITIVE_KEY_PARTS as $sensitiveKeyPart) {
            if (str_contains($normalizedKey, $sensitiveKeyPart)) {
                return true;
            }
        }

        return false;
    }
}
