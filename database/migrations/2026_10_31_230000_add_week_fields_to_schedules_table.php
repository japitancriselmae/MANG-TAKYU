<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->date('week_start_date')->nullable()->after('schedule_id');
            $table->date('week_end_date')->nullable()->after('week_start_date');
            $table->string('schedule_status')->default('draft')->after('status'); // draft, approved, published
            $table->foreignId('shift_template_id')->nullable()
                ->constrained('shift_templates', 'shift_template_id')
                ->nullOnDelete()
                ->after('schedule_status');
            $table->foreignId('created_by')->nullable()
                ->constrained('employees', 'employee_id')
                ->nullOnDelete()
                ->after('shift_template_id');
            $table->foreignId('approved_by')->nullable()
                ->constrained('employees', 'employee_id')
                ->nullOnDelete()
                ->after('created_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['shift_template_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'week_start_date',
                'week_end_date',
                'schedule_status',
                'shift_template_id',
                'created_by',
                'approved_by',
                'approved_at',
            ]);
        });
    }
};
