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
        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->id('product_ingredient_id');

            $table->foreignId('product_id')
                ->constrained('products', 'product_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('ingredient_id')
                ->constrained('ingredients', 'ingredient_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('quantity_required', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ingredients');
    }
};
