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
        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreignId('inventory_category_id')
                ->nullable()
                ->constrained('inventory_categories')
                ->nullOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->index(['status', 'ingredient_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropIndex(['status', 'ingredient_name']);
            $table->dropUnique(['sku']);
            $table->dropConstrainedForeignId('inventory_category_id');
            $table->dropColumn(['sku', 'description', 'purchase_price']);
        });
    }
};
