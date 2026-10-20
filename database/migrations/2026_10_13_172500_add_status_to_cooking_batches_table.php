<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cooking_batches', function (Blueprint $table) {
            $table->string('status')->default('cooking')->after('duration_minutes');
            $table->dateTime('ready_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cooking_batches', function (Blueprint $table) {
            $table->dropColumn(['status', 'ready_at']);
        });
    }
};
