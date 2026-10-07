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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');

            $table->foreignId('order_id')
                ->constrained('orders', 'order_id')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('payment_method');
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('change_amount', 10, 2);
            $table->dateTime('payment_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
