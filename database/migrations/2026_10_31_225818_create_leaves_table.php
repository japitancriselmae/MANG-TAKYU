<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id('leave_id');
            $table->foreignId('employee_id')
                ->constrained('employees', 'employee_id')
                ->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('leave_type');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()
                ->constrained('employees', 'employee_id')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
