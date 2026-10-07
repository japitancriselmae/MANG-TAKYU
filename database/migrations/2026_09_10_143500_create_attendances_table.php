<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id('attendance_id');

            $table->foreignId('employee_id')
                ->constrained('employees', 'employee_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('schedule_id')
                ->constrained('schedules', 'schedule_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->string('status');
            $table->integer('late_minutes')->default(0);
            $table->integer('undertime_minutes')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
