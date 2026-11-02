<?php

use App\Console\Commands\GenerateWeeklySchedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// =====================================================
// WEEKLY SCHEDULE GENERATION
// =====================================================

// Automatically generate next week's draft schedule every Sunday at 00:01
Schedule::command(GenerateWeeklySchedule::class)
    ->weekly()
    ->sundays()
    ->at('00:01');
