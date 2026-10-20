<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Notifications\CookingBatchNotification;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function (ViewContract $view): void {
            $employee = auth()->user()?->employee;
            $roleName = $employee?->role?->role_name;
            $attendanceProofDue = null;
            $kitchenBatchNotifications = collect();
            $kitchenBatchNotificationCount = 0;

            if ($employee && in_array($roleName, ['Cashier', 'Kitchen Staff'], true)) {
                $attendanceProofDue = Attendance::query()
                    ->where('employee_id', $employee->employee_id)
                    ->whereDate('date', today())
                    ->whereNotNull('time_in')
                    ->whereNull('proof_image')
                    ->first();
            }

            if ($roleName === 'Cashier') {
                $user = auth()->user();
                $kitchenBatchNotifications = $user->unreadNotifications()
                    ->where('type', CookingBatchNotification::class)
                    ->latest()
                    ->limit(10)
                    ->get();
                $kitchenBatchNotificationCount = $user->unreadNotifications()
                    ->where('type', CookingBatchNotification::class)
                    ->count();
            }

            $view->with([
                'attendanceProofDue' => $attendanceProofDue,
                'kitchenBatchNotifications' => $kitchenBatchNotifications,
                'kitchenBatchNotificationCount' => $kitchenBatchNotificationCount,
            ]);
        });
    }
}
