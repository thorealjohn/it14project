<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // User who recorded the payment
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'gcash', 'bank_transfer', 'check', 'other'])->default('cash');
            $table->string('payment_reference')->nullable(); // For GCash, bank transfer reference, check number, etc.
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Index for faster queries
            $table->index('order_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
