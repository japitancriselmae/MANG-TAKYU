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
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_fresh_chicken')
                ->default(false)
                ->after('order_type');

            $table->dateTime('estimated_ready_at')
                ->nullable()
                ->after('is_fresh_chicken');

            $table->dateTime('ready_at')
                ->nullable()
                ->after('estimated_ready_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_fresh_chicken',
                'estimated_ready_at',
                'ready_at',
            ]);
        });
    }
};
