<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\ActivityLogger;
use App\Services\SchedulingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateWeeklySchedule extends Command
{
    protected $signature = 'mang-takyu:generate-weekly-schedule
                            {--date= : The week start date (Y-m-d). Defaults to next Monday.}';

    protected $description = 'Generate the weekly employee schedule as draft';

    public function handle(SchedulingService $service, ActivityLogger $activityLogger): int
    {
        $weekStart = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfWeek()
            : Carbon::now()->startOfWeek()->addWeek();

        $weekEnd = $weekStart->copy()->endOfWeek();

        $this->info("Generating weekly schedule for {$weekStart->format('M d, Y')} to {$weekEnd->format('M d, Y')}...");

        // Find a manager user to assign as creator
        $manager = Employee::whereHas('role', function ($q) {
            $q->where('role_name', 'Manager');
        })->first();

        if (! $manager) {
            $this->error('No Manager employee found to assign as schedule creator.');

            return self::FAILURE;
        }

        $result = $service->generateWeeklySchedule(
            $weekStart,
            $weekEnd,
            $manager->employee_id,
            $activityLogger,
        );

        if (! $result['success']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info($result['message']);

        if (! empty($result['shortages'])) {
            $this->warn('Staffing shortages:');
            foreach ($result['shortages'] as $shortage) {
                $this->warn('  - '.$shortage);
            }
        } else {
            $this->info('All shift staffing requirements met.');
        }

        return self::SUCCESS;
    }
}
