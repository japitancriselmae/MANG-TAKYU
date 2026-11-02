<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canViewActivityLogs($user);
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $this->canViewActivityLogs($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function delete(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function restore(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function forceDelete(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    private function canViewActivityLogs(User $user): bool
    {
        $employee = $user->employee;

        return $employee?->status === 'Active'
            && in_array($employee->role?->role_name, ['Admin', 'Manager'], true);
    }
}
