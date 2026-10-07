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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id('inventory_id');

            $table->foreignId('ingredient_id')
                ->constrained('ingredients', 'ingredient_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('quantity', 10, 2);
            $table->dateTime('last_updated');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
