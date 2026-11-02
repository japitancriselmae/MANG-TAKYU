<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table): void {
            $table->time('start_time')->nullable()->change();
            $table->time('end_time')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('schedules')
            ->whereNull('start_time')
            ->orWhereNull('end_time')
            ->update([
                'start_time' => DB::raw("COALESCE(start_time, '00:00:00')"),
                'end_time' => DB::raw("COALESCE(end_time, '00:00:00')"),
            ]);

        Schema::table('schedules', function (Blueprint $table): void {
            $table->time('start_time')->nullable(false)->change();
            $table->time('end_time')->nullable(false)->change();
        });
    }
};
